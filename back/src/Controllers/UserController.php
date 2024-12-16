<?php

namespace App\Controllers;

use App\Helpers\Helpers;
use App\Helpers\JwtHelper;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Регистрация или вход пользователя, хэширование и создание JWT
     */
    public function handleUserLogin(Request $request, Response $response): Response
    {
        // Получаем id пользователя из тела запроса
        $data = $request->getParsedBody();
        $telegramId = $data['telegram_id'] ?? null;

        if (!$telegramId) {
            $response->getBody()->write(json_encode(['error' => 'Telegram ID is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Хэшируем Telegram ID
        $telegramIdHash = Helpers::generateHash($telegramId);

        // Проверяем, есть ли пользователь с таким хэшированным ID
        $user = $this->userRepository->getUserByTelegramIdHash($telegramIdHash);

         if (!$user) {
            // Если пользователя нет в базе, создаем его
            
            $user = ['id' => $this->userRepository->createUser($telegramIdHash)];
        }
        

        // Получаем чаты пользователя (если есть)
        $chats = [];
        if ($user['id']) {
            $chats = $this->userRepository->getUserChats($user['id']);
            foreach ($chats as &$chat) {
                unset($chat['chat_key']); 
            }
        }
       

        // Генерируем JWT
        $jwt = JwtHelper::generateJwt($telegramIdHash);


        $result = [
            'jwt' => $jwt,
            'chats' => $chats,
            'user' => $user
        ]; 

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}