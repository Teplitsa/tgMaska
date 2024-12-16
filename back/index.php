<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Загружаем переменные окружения
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Создание приложения Slim
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Add the RoutingMiddleware before the CORS middleware
// to ensure routing is performed later
$app->addRoutingMiddleware();

// Add the ErrorMiddleware before the CORS middleware
// to ensure error responses contain all CORS headers.
$app->addErrorMiddleware(true, true, true);

// This CORS middleware will append the response header
// Access-Control-Allow-Methods with all allowed methods
$app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($app): ResponseInterface {
    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
    } else {
        $response = $handler->handle($request);
    }

    $response = $response
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->withHeader('Pragma', 'no-cache');

    if (ob_get_contents()) {
        ob_clean();
    }

    return $response;
});
(require __DIR__ . '/routes.php')($app);

// Запуск приложения
$app->run();