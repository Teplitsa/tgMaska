<?php 
namespace App\Controllers;

use App\Repositories\ChatRepository;
use App\Repositories\ChatMemberRepository;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use App\Helpers\Helpers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Redis;

class MessageController{
    private ChatRepository $chatRepository;
    private ChatMemberRepository $chatMemberRepository;
    private UserRepository $userRepository;
    private MessageRepository $messageRepository;

    public function __construct(){
        $this->chatRepository = new ChatRepository();
        $this->chatMemberRepository = new ChatMemberRepository();
        $this->userRepository = new UserRepository();
        $this->messageRepository = new MessageRepository();
    }
    public function getMessages(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $chatId = isset($data['chatId'])? (int)$data['chatId'] : null;
        if (empty($chatId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required parameters: chatId']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $keyGroup = $this->chatRepository->getChatById($chatId);

        if (!$keyGroup) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $messages = $this->messageRepository->getChatMessages($chatId);

        foreach ($messages as &$message) {
            // Применяем расшифровку для каждого сообщения
            $message['decrypted_content'] = Helpers::decryptMessage($message['encrypted_content'], $keyGroup['chat_key']);
            $message['member'] = $this->chatMemberRepository->getChatMember($message['user_id'], $message['chat_id']);
            unset($message['encrypted_content']);
        }

        $response->getBody()->write(json_encode(['messages' => $messages]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function handleCreateMessage(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $creatorId = (int)$data['creatorId'] ?? null;
        $chatId = isset($data['chatId'])? (int)$data['chatId'] : null;
        $message = $data['message'] ?? '';
        
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
        $keyGroup = $this->chatRepository->getChatById($chatId);

        if (!$keyGroup) {
            $response->getBody()->write(json_encode(['error' => 'Chat not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $messageEncript = Helpers::encryptMessage($message, $keyGroup['chat_key']);

       if(!$this->messageRepository->addMessage($chatId, $creatorId, $messageEncript)){
        $response->getBody()->write(json_encode(['error' => 'Cant add message']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
       }
        $redis = new Redis(); 
        $redis->connect('127.0.0.1', 6379);
        $redis->setex('new_record_id', 60, $chatId);
        $redis->publish('sse_notifications', 'cID ' . $chatId);

        $result =[
            'message' => 'add massage',
            

        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}