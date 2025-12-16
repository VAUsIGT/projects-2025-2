<?php

namespace App\Helpers;

class DatabaseHelper
{
    /**
     * Генерация тестовых данных для социальной сети
     */
    public static function generateTestData()
    {
        $users = [];
        $posts = [];
        
        // Генерация пользователей
        $firstNames = ['Иван', 'Анна', 'Петр', 'Мария', 'Алексей', 'Елена', 'Дмитрий', 'Ольга'];
        $lastNames = ['Иванов', 'Петрова', 'Сидоров', 'Смирнова', 'Кузнецов', 'Попова'];
        $cities = ['Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 'Казань'];
        $interestsList = [
            'программирование, спорт, музыка',
            'путешествия, фотография, кулинария',
            'кино, книги, видеоигры',
            'дизайн, искусство, мода',
            'наука, технологии, инновации'
        ];
        
        for ($i = 1; $i <= 10; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            
            $users[] = [
                'username' => strtolower($firstName) . '_' . $i,
                'email' => strtolower($firstName) . $i . '@example.com',
                'full_name' => $firstName . ' ' . $lastName,
                'bio' => 'Привет! Меня зовут ' . $firstName,
                'city' => $cities[array_rand($cities)],
                'age' => rand(20, 40),
                'interests' => $interestsList[array_rand($interestsList)],
                'created_at' => date('Y-m-d', strtotime('-' . rand(0, 30) . ' days'))
            ];
        }
        
        // Генерация постов
        $titles = [
            'Мой отличный день',
            'Новое достижение',
            'Интересная находка',
            'Мысли вслух',
            'Фотоотчет',
            'Рекомендация'
        ];
        
        $hashtags = [
            ['photo', 'day'],
            ['achievement', 'work'],
            ['news', 'interesting'],
            ['thoughts', 'life'],
            ['travel', 'nature'],
            ['music', 'concert'],
            ['food', 'recipe'],
            ['sport', 'training']
        ];
        
        for ($i = 1; $i <= 20; $i++) {
            $userIndex = rand(0, count($users) - 1);
            $postHashtags = $hashtags[array_rand($hashtags)];
            
            $posts[] = [
                'user_id' => $userIndex + 1,
                'username' => $users[$userIndex]['username'],
                'title' => $titles[array_rand($titles)] . ' #' . $i,
                'content' => 'Содержание поста ' . $i . '. ' . 
                           'Это интересный контент о различных аспектах жизни. #' . 
                           implode(' #', $postHashtags),
                'hashtags' => $postHashtags,
                'likes_count' => rand(0, 100),
                'comments_count' => rand(0, 20),
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-' . rand(0, 24*30) . ' hours')),
                'is_public' => true
            ];
        }
        
        return ['users' => $users, 'posts' => $posts];
    }
    
    /**
     * Форматирование вывода JSON
     */
    public static function prettyPrint($data)
    {
        echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
    }
}