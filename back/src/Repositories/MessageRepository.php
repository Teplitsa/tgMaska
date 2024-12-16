<?php

namespace App\Repositories;

use PDO;
use App\Repositories\DatabaseRepository;

class MessageRepository extends DatabaseRepository
{
    public function __construct()
    {
        parent::__construct();  // Подключаемся к базе данных
    }

    /**
     * Добавить сообщение в чат
     *
     * @param int $chatId ID чата
     * @param int $userId ID пользователя, отправившего сообщение
     * @param string $encryptedContent Зашифрованный контент сообщения
     * @return bool Успешность операции
     */
    public function addMessage(int $chatId, int $userId, string $encryptedContent): bool
    {
        $query = "INSERT INTO `messages` (`chat_id`, `user_id`, `encrypted_content`, `created_at`) 
                  VALUES (:chatId, :userId, :encryptedContent, NOW())";

        $params = [
            ':chatId' => $chatId,
            ':userId' => $userId,
            ':encryptedContent' => $encryptedContent,
        ];

        return $this->executeQuery($query, $params);
    }

    /**
     * Получить сообщения чата
     *
     * @param int $chatId ID чата
     * @param int $limit Лимит сообщений для получения
     * @param int $offset Смещение для пагинации
     * @return array Список сообщений
     */
    public function getChatMessages(int $chatId, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT * FROM `messages` 
                  WHERE `chat_id` = :chatId 
                  ORDER BY `created_at` DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':chatId', $chatId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($messages);
    }

    /**
     * Получить сообщения пользователя в чате
     *
     * @param int $userId ID пользователя
     * @param int $chatId ID чата
     * @param int $limit Лимит сообщений
     * @return array Список сообщений пользователя
     */
    public function getUserMessagesInChat(int $userId, int $chatId, int $limit = 20): array
    {
        $query = "SELECT * FROM `messages` 
                  WHERE `chat_id` = :chatId AND `user_id` = :userId 
                  ORDER BY `created_at` DESC 
                  LIMIT :limit";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':chatId', $chatId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Удалить сообщение по ID
     *
     * @param int $messageId ID сообщения
     * @return bool Успешность операции
     */
    public function deleteMessage(int $messageId): bool
    {
        $query = "DELETE FROM `messages` WHERE `id` = :messageId";

        $params = [':messageId' => $messageId];

        return $this->executeQuery($query, $params);
    }
/**
 * Получить сообщения чата с никнеймами
 *
 * @param int $chatId ID чата
 * @param int $limit Лимит сообщений для получения
 * @param int $offset Смещение для пагинации
 * @return array Список сообщений с никнеймами
 */
    public function getChatMessagesWithNicknames(int $chatId, int $limit = 20, int $offset = 0): array
    {
    $query = "
        SELECT 
            m.id AS message_id,
            m.encrypted_content,
            m.created_at,
            cm.nick,
            cm.avatar AS user_avatar,
            cm.role AS user_role,
            m.user_id,
            m.chat_id
        FROM messages m
        INNER JOIN chat_members cm ON m.user_id = cm.user_id AND m.chat_id = cm.chat_id
        WHERE m.chat_id = :chatId
        ORDER BY m.created_at ASC
        LIMIT :limit OFFSET :offset";

    $stmt = $this->pdo->prepare($query);
    $stmt->bindValue(':chatId', $chatId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getChatMessagesAfterDate(int $chatId, string $createdAfter): array
{
    $query = "
        SELECT 
            m.id AS message_id,
            m.encrypted_content,
            m.created_at,
            cm.nick,
            cm.avatar AS user_avatar,
            cm.role AS user_role,
            m.user_id,
            m.chat_id
        FROM messages m
        INNER JOIN chat_members cm ON m.user_id = cm.user_id AND m.chat_id = cm.chat_id
        WHERE m.chat_id = :chatId AND m.created_at > :createdAfter
        ORDER BY m.created_at ASC";

    $stmt = $this->pdo->prepare($query);
    $stmt->bindValue(':chatId', $chatId, PDO::PARAM_INT);
    $stmt->bindValue(':createdAfter', $createdAfter, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function deleteMessagesByUserId(int $userId): bool
{
    $query = "DELETE FROM messages WHERE user_id = :userId";

    $stmt = $this->pdo->prepare($query);
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}
}