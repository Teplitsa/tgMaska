<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Config;

class JwtMiddleware implements MiddlewareInterface
{
    /**
     * Проводит проверку JWT в заголовке Authorization
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Извлекаем заголовок Authorization из запроса
        $authHeader = $request->getHeaderLine('Authorization');

        // Проверяем, что заголовок содержит токен
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            // Если нет токена, возвращаем ошибку
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized, no token provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Извлекаем токен из заголовка
        $jwt = $matches[1];

        try {
            // Проверяем и декодируем токен
            
            $decoded = JWT::decode($jwt, new Key(Config::get('JWT_SECRET'), 'HS256'));

            // Преобразуем декодированный токен в массив и добавляем в атрибуты запроса
            $request = $request->withAttribute('user', (array) $decoded);
        } catch (ExpiredException $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized, token expired']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized, invalid token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Если всё прошло успешно, передаем управление дальше
        return $handler->handle($request);
    }
}