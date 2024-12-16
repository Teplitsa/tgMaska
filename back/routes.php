<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request; 
use App\Controllers\UserController;
use App\Controllers\ChatController;
use App\Controllers\MessageController;
use App\Middleware\JwtMiddleware;
use Slim\App;



return function (App $app) {

    $userController = new UserController();
    $chatController = new ChatController();
    $messageController = new MessageController();
    // Группа защищенных маршрутов
    $app->group('/api', function () use ($app, $chatController, $messageController) {

        $app->get('/', function (Request $request, Response $response, $args) {
            $response->getBody()->write("Hello world!");
            return $response;
        });

        $app->post('/create_chat',[$chatController, 'handleCreateChat']);
        $app->post('/update_link_chat',[$chatController, 'handleUpdateInviteLink']);
        $app->post('/first_join_chat',[$chatController, 'handleFirstJoinChat']);
        $app->post('/add_mess',[$messageController, 'handleCreateMessage']);
        $app->post('/get_mess',[$messageController, 'getMessages']);
        $app->post('/get_info_chat',[$chatController, 'handleGetInfo']);
        $app->post('/exit_chat',[$chatController, 'handleExitChatMember']);
        $app->post('/has_in_chat',[$chatController, 'handleHasChatMemberInChat']);
        $app->post('/del_member',[$chatController, 'handleLeaveOrBanMemberFromChat']);
        $app->post('/del_chat',[$chatController, 'handleDeleteChat']);
        
    })->add(JwtMiddleware::class); 

    // Открытые маршруты
    $app->post('/login',[$userController, 'handleUserLogin']);
};