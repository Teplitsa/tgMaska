<?php

namespace App\Repositories;

use PDO;
use App\Repositories\DatabaseRepository;

class ChatMemberRepository extends DatabaseRepository
{
    public function __construct()
    {
        parent::__construct();  // Подключаемся к базе данных
    }

    /**
     * Добавить пользователя в чат
     *
     * @param int $userId ID пользователя
     * @param int $chatId ID чата
     * @param string $role Роль пользователя в чате (owner, member, banned)
     * @param string $nick Никнейм пользователя в чате
     * @return bool|int Успешность операции
     */
    public function addUserToChat(int $userId, int $chatId, string $role, string $nick = '', string $ava = '', $return = false): bool|int
    {
        // Проверяем, состоит ли пользователь уже в этом чате
        $existingMember = $this->getChatMember($userId, $chatId);
        if ($existingMember) {
            return false;  // Пользователь уже состоит в этом чате
        }

        $query = "INSERT INTO `chat_members` (`user_id`, `chat_id`, `role`, `nick`, `added_at`, `avatar`, `last_exit_at`) 
                  VALUES (:userId, :chatId, :role, :nick, NOW(), :avatar, NOW())";

        $params = [
            ':userId' => $userId,
            ':chatId' => $chatId,
            ':role' => $role,
            ':nick' => $nick,
            ':avatar' => $ava,
        ];
        $result = $this->executeQuery($query, $params);
        // Возвращаем ID созданного чата
        $ID = (int)$this->pdo->lastInsertId();

        if ($return) {
            return $ID;
        }
        return $result;
    }

    public function updateLastExitAt(int $userId, int $chatId): bool
    {
        // Проверяем, есть ли пользователь в чате
        $member = $this->getChatMember($userId, $chatId);
        if (!$member) {
            return false;  // Если пользователя нет в чате, то ничего не обновляем
        }
    
        // Обновляем время последнего выхода
        $query = "UPDATE `chat_members` 
                  SET `last_exit_at` = NOW() 
                  WHERE `user_id` = :userId AND `chat_id` = :chatId";
    
        $params = [
            ':userId' => $userId,
            ':chatId' => $chatId,
        ];
    
        return $this->executeQuery($query, $params);
    }
    
    public function getChatMembersCount(int $chatId): int
    {
        $query = "SELECT COUNT(*) AS member_count FROM `chat_members` WHERE `chat_id` = :chatId";
        
        $params = [
            ':chatId' => $chatId,
        ];

        $result = $this->fetchOne($query, $params);
        
        return $result['member_count'] ?? 0; 
    }

    /**
     * Получить информацию о пользователе в чате
     *
     * @param int $userId ID пользователя
     * @param int $chatId ID чата
     * @return array|null Информация о пользователе в чате
     */
    public function getChatMember(int $userId, int $chatId): ?array
    {
        $query = "SELECT * FROM `chat_members` WHERE `user_id` = :userId AND `chat_id` = :chatId LIMIT 1";
        $params = [':userId' => $userId, ':chatId' => $chatId];

        return $this->fetchOne($query, $params);
    }

    /**
     * Получить всех участников чата
     *
     * @param int $chatId ID чата
     * @return array Список участников чата
     */
    public function getChatMembers(int $chatId): array
    {
        $query = "SELECT * FROM `chat_members` WHERE `chat_id` = :chatId";
        $params = [':chatId' => $chatId];

        return $this->fetchAll($query, $params);
    }

    /**
     * Обновить роль пользователя в чате
     *
     * @param int $userId ID пользователя
     * @param int $chatId ID чата
     * @param string $role Новая роль пользователя
     * @return bool Успешность операции
     */
    public function updateRole(int $userId, int $chatId, string $role): bool
    {
        $query = "UPDATE `chat_members` SET `role` = :role, `updated_at` = NOW() 
                  WHERE `user_id` = :userId AND `chat_id` = :chatId";

        $params = [
            ':role' => $role,
            ':userId' => $userId,
            ':chatId' => $chatId
        ];

        return $this->executeQuery($query, $params);
    }

    /**
     * Обновить никнейм пользователя в чате
     *
     * @param int $userId ID пользователя
     * @param int $chatId ID чата
     * @param string $nick Новый никнейм пользователя
     * @return bool Успешность операции
     */
    public function updateNick(int $userId, int $chatId, string $nick): bool
    {
        $query = "UPDATE `chat_members` SET `nick` = :nick, `updated_at` = NOW() 
                  WHERE `user_id` = :userId AND `chat_id` = :chatId";

        $params = [
            ':nick' => $nick,
            ':userId' => $userId,
            ':chatId' => $chatId
        ];

        return $this->executeQuery($query, $params);
    }

    /**
     * Удалить пользователя из чата
     *
     * @param int $userId ID пользователя
     * @param int $chatId ID чата
     * @return bool Успешность операции
     */
    public function removeUserFromChat(int $userId, int $chatId): bool
    {
        $query = "DELETE FROM `chat_members` WHERE `user_id` = :userId AND `chat_id` = :chatId";
        $params = [':userId' => $userId, ':chatId' => $chatId];

        return $this->executeQuery($query, $params);
    }
}