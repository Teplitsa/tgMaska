<?php 

namespace App;

class Config {
    /**
     * Получение конфигурационного параметра из .env
     *
     * @param string $key Ключ конфигурации
     * @param string $default Значение по умолчанию, если параметр не найден
     * @return string
     */
    public static function get(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? $default;
    }
}