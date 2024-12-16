<?php

namespace App\Repositories;
use App\Config;
use PDO;
use PDOException;
use Exception;

abstract class DatabaseRepository
{
    protected PDO $pdo;


    public function __construct()
    {
        // Получаем параметры для подключения к базе данных из конфигурации
        $dbHost = Config::get('DB_HOST', 'localhost');
        $dbName = Config::get('DB_NAME', 'test_db');
        $dbUser = Config::get('DB_USER', 'root');
        $dbPassword = Config::get('DB_PASSWORD', '');

        // Создаем подключение к базе данных
        try {
            $this->pdo = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8",
                $dbUser,
                $dbPassword,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }


    protected function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Выполнить запрос на вставку/обновление/удаление
     *
     * @param string $query SQL-запрос
     * @param array $params Параметры запроса
     * @return bool
     */
    protected function executeQuery(string $query, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

     /**
     * Выполнить запрос на вставку/обновление/удаление
     *
     * @param string $query SQL-запрос
     * @param array $params Параметры запроса
     * @return bool|int
     */
    protected function executeQueryID(string $query, array $params = []): int|bool
    {
        $stmt = $this->pdo->prepare($query);
        $result = $stmt->execute($params);
        
        if ($result) {
            return (int)$this->pdo->lastInsertId();  // Возвращаем ID последней вставленной строки
        }
        
        return false;
    }

    /**
     * Найти одну запись по ID
     *
     * @param string $query SQL-запрос
     * @param array $params Параметры запроса
     * @return array|null
     */
    protected function fetchOne(string $query, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}