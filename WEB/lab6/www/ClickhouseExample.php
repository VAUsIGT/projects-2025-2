<?php

namespace App;

use App\Helpers\ClientFactory;

class ClickhouseExample
{
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://clickhouse:8123/');
    }

    /**
     * Создание таблицы для аналитики пользователей
     */
    public function createUserAnalyticsTable()
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS user_analytics (
            event_date Date,
            user_id String,
            event_type String,
            event_data String,
            timestamp DateTime DEFAULT now()
        ) ENGINE = MergeTree()
        ORDER BY (event_date, user_id, event_type)
        SQL;

        return $this->query($sql);
    }

    /**
     * Создание таблицы для лайков
     */
    public function createLikesTable()
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS likes (
            post_id String,
            user_id String,
            liked_at DateTime DEFAULT now()
        ) ENGINE = MergeTree()
        ORDER BY (post_id, user_id)
        SQL;

        return $this->query($sql);
    }

    /**
     * Логирование активности пользователя
     */
    public function logUserActivity($userId, $eventType, $eventData)
    {
        $userId = $this->escapeString($userId);
        $eventType = $this->escapeString($eventType);
        $eventData = $this->escapeString($eventData);
        
        $sql = "INSERT INTO user_analytics (event_date, user_id, event_type, event_data) VALUES (today(), '{$userId}', '{$eventType}', '{$eventData}')";
        return $this->query($sql);
    }

    /**
     * Добавление лайка
     */
    public function addLike($postId, $userId)
    {
        $postId = $this->escapeString($postId);
        $userId = $this->escapeString($userId);
        
        $sql = "INSERT INTO likes (post_id, user_id) VALUES ('{$postId}', '{$userId}')";
        return $this->query($sql);
    }

    /**
     * Статистика активности пользователей
     */
    public function getUserActivityStats($days = 7)
    {
        $sql = <<<SQL
        SELECT 
            event_date,
            event_type,
            COUNT(*) as count,
            uniqExact(user_id) as unique_users
        FROM user_analytics 
        WHERE event_date >= today() - {$days}
        GROUP BY event_date, event_type
        ORDER BY event_date DESC, count DESC
        SQL;

        return $this->query($sql);
    }

    /**
     * Популярные посты
     */
    public function getPopularPosts($limit = 10)
    {
        $sql = "SELECT post_id, COUNT(*) as likes_count FROM likes GROUP BY post_id ORDER BY likes_count DESC LIMIT {$limit}";
        return $this->query($sql);
    }

    /**
     * Экранирование строк для SQL
     */
    private function escapeString($value)
    {
        return str_replace(["'", "\\"], ["''", "\\\\"], $value);
    }

    /**
     * Выполнение запроса
     */
    public function query($sql)
    {
        try {
            $response = $this->client->post('', [
                'body' => $sql,
                'headers' => [
                    'Content-Type' => 'text/plain'
                ]
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            // Логируем ошибку, но не падаем
            error_log("ClickHouse error: " . $e->getMessage());
            return '';
        }
    }
}