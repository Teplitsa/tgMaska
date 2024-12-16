<?php

namespace App\Repositories;

use PDO;
use App\Config\Config;
use App\Repositories\DatabaseRepository;

class UserRepository extends DatabaseRepository
{
    public function __construct()
    {
        parent::__construct();  // Подключаемся к базе данных
    }

    /**
     * Создание нового пользователя
     *
     * @param string $telegramIdHash Хэш Telegram ID пользователя
     * @return int ID нового пользователя
     */
    public function createUser(string $telegramIdHash): int
    {
        $query = "INSERT INTO `users` (`telegram_id_hash`, `registered_at`, `updated_at`) 
                  VALUES (:telegramIdHash, NOW(), NOW())";

        $params = [
            ':telegramIdHash' => $telegramIdHash,
        ];

        $this->executeQuery($query, $params);

        // Возвращаем ID нового пользователя
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Получить пользователя по Telegram ID hash
     *
     * @param string $telegramIdHash Хэш Telegram ID пользователя
     * @return array|null Информация о пользователе
     */
    public function getUserByTelegramIdHash(string $telegramIdHash): ?array
    {
        $query = "SELECT * FROM `users` WHERE `telegram_id_hash` = :telegramIdHash LIMIT 1";
        $params = [':telegramIdHash' => $telegramIdHash];

        return $this->fetchOne($query, $params);
    }

    /**
     * Обновить информацию о пользователе
     *
     * @param int $userId ID пользователя
     * @param array $data Данные для обновления (например, новый Telegram ID hash, обновление времени)
     * @return bool Успешность операции
     */
    public function updateUser(int $userId, array $data): bool
    {
        $setClauses = [];
        $params = [':userId' => $userId];

        foreach ($data as $key => $value) {
            $setClauses[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }

        $setQuery = implode(", ", $setClauses);
        $query = "UPDATE `users` SET $setQuery, `updated_at` = NOW() WHERE `id` = :userId";

        return $this->executeQuery($query, $params);
    }

    /**
     * Получить все пользователей
     *
     * @return array Список всех пользователей
     */
    public function getAllUsers(): array
    {
        $query = "SELECT * FROM `users`";
        return $this->fetchAll($query);
    }

    /**
     * Удалить пользователя по ID
     *
     * @param int $userId ID пользователя
     * @return bool Успешность операции
     */
    public function deleteUser(int $userId): bool
    {
        $query = "DELETE FROM `users` WHERE `id` = :userId";
        $params = [':userId' => $userId];

        return $this->executeQuery($query, $params);
    }

    public function getUserChats(int $userId): array
    {
        $query = "
        SELECT 
            c.id AS chat_id, 
            c.chat_key, 
            c.creator_id, 
            c.type, 
            c.invite_link, 
            c.name, 
            c.avatar,
            (SELECT COUNT(*) FROM tgChat.messages m WHERE m.chat_id = c.id AND cm.last_exit_at < m.created_at) AS message_count
        FROM chats c
        INNER JOIN chat_members cm ON cm.chat_id = c.id
        WHERE cm.user_id = :userId
    ";

    // Выполнение запроса и возврат результатов
    return $this->fetchAll($query, ['userId' => $userId]);
    }
}