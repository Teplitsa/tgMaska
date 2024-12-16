<?php 

namespace App\Controllers;

use App\Repositories\ChatRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ChatMemberRepository;
use App\Repositories\UserRepository;
use App\Helpers\Helpers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Redis;

class ChatController{

    private ChatRepository $chatRepository;
    private ChatMemberRepository $chatMemberRepository;
    private UserRepository $userRepository;
    private MessageRepository $messageRepository;

    public function __construct()
    {
        $this->chatRepository = new ChatRepository();
        $this->chatMemberRepository = new ChatMemberRepository();
        $this->userRepository = new UserRepository();
        $this->messageRepository = new MessageRepository();
    }

    public function handleCreateChat(Request $request, Response $response): Response
    {
        // Получаем параметры из запроса
        $data = $request->getParsedBody();

        $creatorId = (int)$data['creatorId'] ?? null;
        $type = $data['type'] ?? 'private';
        $name = $data['name'] ?? '';
        $nick = $data['nick'] ?? '';

        // Получаем telegram_id_hash из атрибутов запроса (из мидлвара)
        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Проверяем, что все обязательные параметры переданы
        if (empty($creatorId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: creatorId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Проверяем, что все обязательные параметры переданы
        if (empty($nick)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: nick']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Дополнительные проверки на корректность
        if (!in_array($type, ['group', 'private'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid chat type']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Сравниваем creatorId с id пользователя из базы данных
        if ($creatorId !== $userRecord['id']) {
            $response->getBody()->write(json_encode(['error' => 'Creator ID does not match the authenticated user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Генерация аватара для чата
        $ava = Helpers::generatePixelArtAvatarBase64();
        $ava_2 = Helpers::generatePixelArtAvatarBase64();
        // Генерация пригласительной ссылки
        $inviteLink = Helpers::generateRandomString(6); // Можно настроить длину

        // Генерация ключа для чата
        $chatKeyBinary = sodium_crypto_secretbox_keygen();

        // Создаём чат
        try {
            $chatId = $this->chatRepository->createChat($chatKeyBinary, $creatorId, $type, $inviteLink, $name, $ava);
        } catch (\Exception $e) {
            // Логирование ошибки создания чата
            error_log('Error creating chat: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error creating chat']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        // Добавляем создателя чата в таблицу участников
        try {
            $memberID = $this->chatMemberRepository->addUserToChat($creatorId, $chatId, 'owner', $nick, $ava_2, true);
    
        } catch (\Exception $e) {
            // Логирование ошибки добавления участника
            error_log('Error adding user to chat: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error adding user to chat']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
        /* $redis = new Redis(); 
        $redis->connect('127.0.0.1', 6379);
        $redis->setex('new_record_id', 60, $chatId);
        $redis->publish('sse_notifications', 'cID ' . $chatId); */
        // Отправляем успешный ответ
        $result =[
            'message' => 'Chat created successfully',
            'chat' => [
                'id' => $chatId,
                'type' => $type,
                'name' => $name,
                'avatar' => $ava,
                'inviteLink' => Helpers::getInviteLink($inviteLink)
            ],
            'chatMember' => [
                'avatar' => $ava_2,
                'nickname' => $nick,
                'id' => $memberID
            ]

        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function handleUpdateInviteLink(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $creatorId = (int)$data['creatorId'] ?? null;
        $chatId = isset($data['chatId'])? (int)$data['chatId'] : null;
        if (empty($creatorId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: creatorId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (empty($chatId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: chatId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Получаем telegram_id_hash из атрибутов запроса (из мидлвара)
        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Сравниваем creatorId с id пользователя из базы данных
        if ($creatorId !== $userRecord['id']) {
            $response->getBody()->write(json_encode(['error' => 'Creator ID does not match the authenticated user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $inviteLink = Helpers::generateRandomString(6);
        // Обновляем ссылку для приглашения
        try {
            
            $update = [];
            $update['invite_link'] = $inviteLink;
            $this->chatRepository->updateChat($chatId, $update);
        } catch (\Exception $e) {
            // Логирование ошибки создания чата
            error_log('Error update link chat: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error update link chat']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
        $result =[
            'message' => 'Chat link updated successfully',
            'inviteLink' => Helpers::getInviteLink($inviteLink)
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function handleFirstJoinChat(Request $request, Response $response): Response
    {
        // Получаем параметры из запроса
        $data = $request->getParsedBody();

        $link = $data['inviteCode'] ?? null;
        $memberId = (int)$data['memberId'] ?? null;
        $nick = $data['nick'] ?? '';
        if (empty($link)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: inviteCode']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (empty($memberId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: memberId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (empty($nick)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: nick']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        // Получаем telegram_id_hash из атрибутов запроса (из мидлвара)
        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        // Сравниваем creatorId с id пользователя из базы данных
        if ($memberId !== $userRecord['id']) {
            $response->getBody()->write(json_encode(['error' => 'Member ID does not match the authenticated user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        // Получаем чат по ссылке
        $chat = $this->chatRepository->getChatByCode($link);
        if (!$chat) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if($chat['type'] == 'private'){
            $countMembersInChat = $this->chatMemberRepository->getChatMembersCount((int)$chat['id']);
            if($countMembersInChat >= 2){
                $response->getBody()->write(json_encode(['error' => 'Private chat is full']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        }

        $result =[
            'message' => 'Join successfully',
            'chat' => [
                'chat_id' => $chat['id'],
                'type' => $chat['type'],
                'name' => $chat['name'],
                'avatar' => $chat['avatar']
            ]

        ];
        // Получаем участника чата по id
        $chatMember = $this->chatMemberRepository->getChatMember( $memberId, $chat['id']);

        if ($chatMember) {
            $result['chatMember'] = [
                'avatar' => $chatMember['avatar'],
                'nick' => $chatMember['nick'],
                'user_id' => $chatMember['user_id']
            ];
        }else{
            $ava = Helpers::generatePixelArtAvatarBase64();
            
            try {

               
                $memberID = $this->chatMemberRepository->addUserToChat($memberId, $chat['id'], 'member', $nick, $ava, true);
        
            } catch (\Exception $e) {
                // Логирование ошибки добавления участника
                error_log('Error adding user to chat when JOIN: ' . $e->getMessage());
                $response->getBody()->write(json_encode(['error' => 'Error adding user to chat']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            $result['chatMember'] = [
                'avatar' => $ava,
                'nick' => $nick,
                'user_id' => $memberID
            ];
        }
        $redis = new Redis(); 
        $redis->connect('127.0.0.1', 6379);
        $redis->setex('new_record_id', 60, $chat['id']);
        $redis->publish('sse_notifications', 'uID ' . $chat['id']);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

    }


    public function handleHasChatMemberInChat(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $memberId = (int)$data['memberId'] ?? null;
        $inviteLink = $data['invite_link'] ?? null;

        if (empty($memberId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: memberId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (empty($inviteLink)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: invite_link']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Сравниваем creatorId с id пользователя из базы данных
        if ($memberId !== $userRecord['id']) {
            $response->getBody()->write(json_encode(['error' => 'Member ID does not match the authenticated user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Получаем чат по ссылке
        $chat = $this->chatRepository->getChatByCode($inviteLink);
        if (!$chat) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if($this->chatMemberRepository->getChatMember($memberId, $chat['id'])){
            $response->getBody()->write(json_encode(['message' => 'User is already in the chat', 'id' => $chat['id']]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $response->getBody()->write(json_encode(['message' => 'User is not in the chat']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function handleExitChatMember(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $chatId = (int)$data['chatId'] ?? null;
        $memberId = (int)$data['memberId'] ?? null;

        if (empty($chatId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: chatId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (empty($memberId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: memberId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Получаем чат по ссылке
        $chat = $this->chatRepository->getChatById($chatId);
        if (!$chat) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        if(!$this->chatMemberRepository->updateLastExitAt($memberId, $chatId)){
            $response->getBody()->write(json_encode(['error' => 'Failed to update exit time']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
        $response->getBody()->write(json_encode(['message' => 'Exit successfully']));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
    
    public function handleGetInfo(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $chatId = (int)$data['chatId'] ?? null;

        if (empty($chatId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: chatId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        // Получаем telegram_id_hash из атрибутов запроса (из мидлвара)
        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Получаем чат по ссылке
        $chat = $this->chatRepository->getChatById($chatId);
        if (!$chat) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $members = $this->chatMemberRepository->getChatMembers($chatId);
        $isOwner = false;

        foreach ($members as &$member) {
            if ($member['user_id'] === $userRecord['id'] && $member['role'] === 'owner') {
                $isOwner = true;
            }
        }

        usort($members, function ($a, $b) {
            if ($a['role'] === 'owner' && $b['role'] !== 'owner') {
                return -1; // $a идет перед $b
            }
            if ($a['role'] !== 'owner' && $b['role'] === 'owner') {
                return 1; // $b идет перед $a
            }
            return 0; // Порядок не меняется для остальных случаев
        });
        $result =[
            'message' => 'successfully',
            'chat' => [
                'chat_id' => $chat['id'],
                'type' => $chat['type'],
                'name' => $chat['name'],
                'avatar' => $chat['avatar'],
                'count_members'=>count($members),
                'members' => $members,
                'inviteLink' => Helpers::getInviteLink($chat['invite_link']),
                'isOwner' => $isOwner
            ]

        ];
        $response->getBody()->write(json_encode($result)); 
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
    public function handleDeleteChat(Request $request, Response $response):Response
    {
        $data = $request->getParsedBody();
        $chatId = (int)$data['chatId'] ?? null;
        if (empty($chatId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: chatId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $chat = $this->chatRepository->getChatById($chatId);
        if (!$chat) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $member = $this->chatMemberRepository->getChatMember($userRecord['id'], $chatId);
        if (!$member) {
            $response->getBody()->write(json_encode(['error' => 'User is not a member of this chat']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        $isOwner = false;
        if($member['role'] === 'owner'){
            $isOwner = true;
        }

        if($isOwner){
            $this->chatRepository->deleteChat($chatId);
            $response->getBody()->write(json_encode(['message' => 'Chat deleted successfully']));
            $redis = new Redis(); 
            $redis->connect('127.0.0.1', 6379);
            $redis->setex('new_record_id', 60, $chatId);
            $redis->publish('sse_notifications', 'dID ' . $chatId);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $response->getBody()->write(json_encode(['message' => 'Chat deleted error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    public function handleLeaveOrBanMemberFromChat(Request $request, Response $response):Response
    {
        $data = $request->getParsedBody();
        $chatId = (int)$data['chatId'] ?? null;
        $memberId = (int)$data['memberId'] ?? null;
        
        if (empty($chatId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: chatId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (empty($memberId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: memberId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $request->getAttribute('user');
        $telegram_id_hash = $user['userId'];

        // Получаем пользователя по telegram_id_hash
        $userRecord = $this->userRepository->getUserByTelegramIdHash($telegram_id_hash);
        if (!$userRecord) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Получаем чат по ссылке
        $chat = $this->chatRepository->getChatById($chatId);
        if (!$chat) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if(!$this->chatMemberRepository->getChatMember($memberId, $chat['id'])){
            $response->getBody()->write(json_encode(['message' => 'User is not in the chat', 'id' => $chat['id']]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $members = $this->chatMemberRepository->getChatMembers($chatId);
        $isOwner = false;

        foreach ($members as &$member) {
            if ($member['user_id'] === $userRecord['id'] && $member['role'] === 'owner') {
                $isOwner = true;
            }
        }

        if($this->chatMemberRepository->removeUserFromChat($memberId, $chatId)){
            $redis = new Redis(); 
            $redis->connect('127.0.0.1', 6379);
            $redis->setex('new_record_id', 60, $chatId);
            $redis->publish('sse_notifications', 'uID ' . $chatId);
            if($this->messageRepository->deleteMessagesByUserId($memberId)){

                $redis->setex('new_record_id', 60, $chatId);
                $redis->publish('sse_notifications', 'cID ' . $chatId);
                $response->getBody()->write(json_encode(['message' => 'User left or banned successfully']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
        }
        $response->getBody()->write(json_encode(['error' => 'Trable']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}