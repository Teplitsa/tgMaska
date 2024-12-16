<?php

namespace App\Repositories;

use PDO;
use App\Config\Config;
use App\Repositories\DatabaseRepository;

class ChatRepository extends DatabaseRepository
{
    public function __construct()
    {
        parent::__construct();  
    }

/**
     * Создание нового чата
     *
     * @param string $chatKey Ключ чата (зашифрованный)
     * @param int $creatorId ID создателя чата
     * @param string $type Тип чата (group/private)
     * @param string|null $inviteLink Пригласительная ссылка (если есть)
     * @return int ID созданного чата
     */
    public function createChat(string $chatKey, int $creatorId, string $type, ?string $inviteLink = null, string $name = '', string $ava = ''): int
    {
        $query = "INSERT INTO `chats` (`chat_key`, `creator_id`, `type`, `invite_link`, `created_at`, `updated_at`, `avatar`, `name`) 
                  VALUES (:chatKey, :creatorId, :type, :inviteLink, NOW(), NOW(), :avatar, :name)";

        $params = [
            ':chatKey' => $chatKey,
            ':creatorId' => $creatorId,
            ':type' => $type,
            ':inviteLink' => $inviteLink,
            ':name' => $name,
            ':avatar' => $ava,
        ];

        $this->executeQuery($query, $params);

        // Возвращаем ID созданного чата
        return (int)$this->pdo->lastInsertId();
    }

       /**
     * Получить чат по ID
     *
     * @param int $chatId ID чата
     * @return array|null Информация о чате
     */
    public function getChatById(int $chatId): ?array
    {
        $query = "SELECT * FROM `chats` WHERE `id` = :chatId LIMIT 1";
        $params = [':chatId' => $chatId];

        return $this->fetchOne($query, $params);
    }

    /**
     * Получить чат по коду (по invite_link)
     *
     * @param int $invite_link ID пользователя (создателя чата)
     * @return array|null Информация о чате
     */
    public function getChatByCode(string $invite_link): ?array
    {
        $query = "SELECT * FROM `chats` WHERE `invite_link` = :invite_link";
        $params = [':invite_link' => $invite_link];

        return $this->fetchOne($query, $params);
    }

    /**
     * Получить все чаты для конкретного пользователя (по creator_id)
     *
     * @param int $userId ID пользователя (создателя чата)
     * @return array Список чатов
     */
    public function getChatsByCreatorId(int $userId): array
    {
        $query = "SELECT * FROM `chats` WHERE `creator_id` = :userId";
        $params = [':userId' => $userId];

        return $this->fetchAll($query, $params);
    }

    /**
     * Обновить информацию о чате
     *
     * @param int $chatId ID чата
     * @param array $data Данные для обновления (например, новый тип чата, ссылка и т.д.)
     * @return bool Успешность операции
     */
    public function updateChat(int $chatId, array $data): bool
    {
        $setClauses = [];
        $params = [':chatId' => $chatId];

        foreach ($data as $key => $value) {
            $setClauses[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }

        $setQuery = implode(", ", $setClauses);
        $query = "UPDATE `chats` SET $setQuery, `updated_at` = NOW() WHERE `id` = :chatId";

        return $this->executeQuery($query, $params);
    }

    /**
     * Удалить чат по ID
     *
     * @param int $chatId ID чата
     * @return bool Успешность операции
     */
    public function deleteChat(int $chatId): bool
    {
        $query = "DELETE FROM `chats` WHERE `id` = :chatId";
        $params = [':chatId' => $chatId];

        return $this->executeQuery($query, $params);
    }

    /**
     * Проверить существование чата по пригласительной ссылке
     *
     * @param string $inviteLink Пригласительная ссылка
     * @return array|null Информация о чате
     */
    public function getChatByInviteLink(string $inviteLink): ?array
    {
        $query = "SELECT * FROM `chats` WHERE `invite_link` = :inviteLink LIMIT 1";
        $params = [':inviteLink' => $inviteLink];

        return $this->fetchOne($query, $params);
    }
}