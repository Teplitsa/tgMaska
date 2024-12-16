<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use App\Config;

class JwtHelper {
    /**
     * Генерация JWT
     * 
     * @param string $userId Хэшированный ID пользователя
     * @return string JWT токен
     */
    public static function generateJwt(string $userId): string
    {
        $key = Config::get('JWT_SECRET');  // Получаем секретный ключ из конфигурации
        $issuedAt = time();
        $expirationTime = $issuedAt + Config::get('JWT_LIVE');  
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $userId
        ];
        $algorithm = 'HS256';
        return JWT::encode($payload, $key, $algorithm);  // Возвращаем сгенерированный JWT
    }

    /**
     * Декодирование JWT
     * 
     * @param string $jwt JWT токен
     * @return object|null Декодированный токен или null в случае ошибки
     */
    public static function decodeJwt(string $jwt)
    {
        $key = Config::get('JWT_SECRET');  // Получаем секретный ключ из конфигурации
        try {
            return JWT::decode($jwt, $key, ['HS256']);
        } catch (\Exception $e) {
            return null;
        }
    }
}