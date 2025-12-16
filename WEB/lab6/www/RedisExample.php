<?php

namespace App;

use Predis\Client;

class RedisExample
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'host' => 'redis',
            'port' => 6379,
            'timeout' => 5.0
        ]);
    }

    /**
     * Кеширование профиля пользователя
     */
    public function cacheUserProfile($userId, $profile, $ttl = 3600)
    {
        $key = "user:profile:$userId";
        return $this->client->setex($key, $ttl, json_encode($profile));
    }

    /**
     * Получение кешированного профиля
     */
    public function getCachedUserProfile($userId)
    {
        $key = "user:profile:$userId";
        $data = $this->client->get($key);
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Онлайн статус пользователя
     */
    public function setOnlineStatus($userId, $status = 'online', $ttl = 300)
    {
        $key = "user:online:$userId";
        return $this->client->setex($key, $ttl, $status);
    }

    /**
     * Проверка онлайн статуса
     */
    public function isUserOnline($userId)
    {
        $key = "user:online:$userId";
        $status = $this->client->get($key);
        return $status && $status !== 'offline';
    }

    /**
     * Кеширование ленты новостей
     */
    public function cacheUserFeed($userId, $posts, $ttl = 60)
    {
        $key = "user:feed:$userId";
        return $this->client->setex($key, $ttl, json_encode($posts));
    }

    /**
     * Получение кешированной ленты
     */
    public function getCachedUserFeed($userId)
    {
        $key = "user:feed:$userId";
        $data = $this->client->get($key);
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Удаление ключа
     */
    public function delete($key)
    {
        return $this->client->del($key);
    }
}