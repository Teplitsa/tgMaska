<?php

namespace App\Helpers;
use App\Config;

class Helpers {
/**
     * Генерация хэша с солью
     * 
     * @param string $userId ID пользователя
     * @param string $salt Соль для хэширования
     * @return string Хэшированный результат
     */
    public static function generateHash(string $userId, string $salt = null): string
    {
        // Если соль не передана, получаем её из конфигурации
        if ($salt === null) {
            $salt = Config::get('HASH_SALT');
        }
        
        return hash('sha256', $userId . $salt);
    }

    public static function getInviteLink(string $uud):string
    {
        return Config::get('URL_APP').$uud;
    }

    public static function encryptMessage(string $message,  $groupKey): string
    {
        // Получаем общий ключ для группы из конфигурации или базы данных
        // Например, мы храним ключи в базе данных, используя group_id как индекс
        
        
        // Генерация уникального nonce для каждого сообщения
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        
        // Шифруем сообщение
        $ciphertext = sodium_crypto_secretbox($message, $nonce, $groupKey);
        
        // Возвращаем зашифрованное сообщение с nonce в начале
        return $nonce . $ciphertext;
    }

    public static function decryptMessage(string $encryptedMessage, $groupKey): string
    {
        // Получаем общий ключ для группы


        // Извлекаем nonce (первые SODIUM_CRYPTO_SECRETBOX_NONCEBYTES байтов)
        $nonce = substr($encryptedMessage, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // Извлекаем зашифрованный текст (остальная часть сообщения)
        $ciphertext = substr($encryptedMessage, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // Дешифруем сообщение
        $message = sodium_crypto_secretbox_open($ciphertext, $nonce, $groupKey);

        if ($message === false) {
            throw new \Exception('Failed to decrypt message');
        }

        return $message;
    }

    public static function generatePixelArtAvatarBase64() {
        // Размеры аватара и маленьких квадратов
        $width = 40;
        $height = 40;
        $blockSize = 8;
    
        // Создаём пустое изображение
        $image = imagecreatetruecolor($width, $height);
    
        // Цвет фона (белый)
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
    
        // Генерируем фиксированный цвет, убедившись, что он не белый
        do {
            $r = rand(0, 255);
            $g = rand(0, 255);
            $b = rand(0, 255);
        } while ($r > 240 && $g > 240 && $b > 240); // Проверяем, что цвет не близок к белому
    
        $fixedColor = imagecolorallocate($image, $r, $g, $b);
    
        // Заполняем фон белым
        imagefill($image, 0, 0, $backgroundColor);
    
        // Генерация квадратиков 8x8
        for ($y = 0; $y < $height; $y += $blockSize) {
            for ($x = 0; $x < $width; $x += $blockSize) {
                // Выбираем случайно белый или фиксированный цвет
                $color = rand(0, 1) ? $backgroundColor : $fixedColor;
    
                // Рисуем квадратик
                imagefilledrectangle($image, $x, $y, $x + $blockSize - 1, $y + $blockSize - 1, $color);
            }
        }
    
        // Сохраняем изображение в буфер
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
    
        // Очищаем ресурсы
        imagedestroy($image);
    
        // Кодируем изображение в Base64
        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    public static function generateRandomString($length = 6): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ=_';
        $charactersLength = strlen($characters);
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
    
        return $randomString;
    }

}