<?php
require_once 'config.php';

echo "<h1>Инициализация базы данных</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

// Временное решение: создадим отдельный клиент для инициализации
use GuzzleHttp\Client;

// Проверка доступности сервисов
function checkService($name, $url) {
    echo "<h3>Проверка $name...</h3>";
    try {
        $client = new Client(['timeout' => 5]);
        $response = $client->get($url);
        echo "<p class='success'>✓ $name доступен (код: {$response->getStatusCode()})</p>";
        return true;
    } catch (Exception $e) {
        echo "<p class='error'>✗ $name недоступен: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Проверяем сервисы
$elasticAvailable = checkService('Elasticsearch', 'http://elasticsearch:9200');
$redisAvailable = true; // Redis проверяем отдельно через Predis
$clickhouseAvailable = checkService('ClickHouse', 'http://clickhouse:8123');

try {
    // Elasticsearch инициализация
    echo "<h2>Инициализация Elasticsearch</h2>";
    
    if ($elasticAvailable && $elastic) {
        // Создаем временный клиент для прямого доступа к Elasticsearch API
        $elasticClient = new Client(['base_uri' => 'http://elasticsearch:9200/']);
        
        try {
            // Удаляем старые индексы если есть
            try {
                $elasticClient->delete('users');
                echo "<p>Старый индекс 'users' удален</p>";
            } catch (Exception $e) {
                // Игнорируем, если индекс не существует
                if (strpos($e->getMessage(), 'index_not_found_exception') === false) {
                    throw $e;
                }
            }
            
            try {
                $elasticClient->delete('posts');
                echo "<p>Старый индекс 'posts' удален</p>";
            } catch (Exception $e) {
                // Игнорируем, если индекс не существует
                if (strpos($e->getMessage(), 'index_not_found_exception') === false) {
                    throw $e;
                }
            }
            
            // Создаем индексы через наш класс
            $usersIndex = $elastic->createUsersIndex();
            echo "<p class='success'>Индекс 'users' создан</p>";
            
            $postsIndex = $elastic->createPostsIndex();
            echo "<p class='success'>Индекс 'posts' создан</p>";
            
            // Добавляем тестовых пользователей
            echo "<h3>Добавление тестовых пользователей</h3>";
            $testUsers = [
                [
                    'username' => 'ivan_petrov',
                    'email' => 'ivan@example.com',
                    'full_name' => 'Иван Петров',
                    'bio' => 'Разработчик из Москвы',
                    'city' => 'Москва',
                    'age' => 28,
                    'interests' => 'программирование, спорт, музыка',
                    'created_at' => date('Y-m-d\TH:i:s')
                ],
                [
                    'username' => 'anna_smirnova',
                    'email' => 'anna@example.com',
                    'full_name' => 'Анна Смирнова',
                    'bio' => 'Дизайнер из Санкт-Петербурга',
                    'city' => 'Санкт-Петербург',
                    'age' => 25,
                    'interests' => 'дизайн, искусство, путешествия',
                    'created_at' => date('Y-m-d\TH:i:s')
                ]
            ];
            
            foreach ($testUsers as $index => $user) {
                $userId = $index + 1;
                $result = $elastic->addUser($user);
                echo "<p>Добавлен пользователь: {$user['username']} (ID: user_{$userId})</p>";
            }
            
            // Добавляем тестовые посты
            echo "<h3>Добавление тестовых постов</h3>";
            $testPosts = [
                [
                    'user_id' => '1',
                    'username' => 'ivan_petrov',
                    'title' => 'Мой первый пост',
                    'content' => 'Сегодня начал изучать Elasticsearch. Интересная технология! #programming #elasticsearch',
                    'hashtags' => ['programming', 'elasticsearch'],
                    'likes_count' => 15,
                    'comments_count' => 3,
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-2 days')),
                    'is_public' => true
                ],
                [
                    'user_id' => '2',
                    'username' => 'anna_smirnova',
                    'title' => 'Новый дизайн',
                    'content' => 'Завершила работу над новым проектом. Что думаете? #design #ui #ux',
                    'hashtags' => ['design', 'ui', 'ux'],
                    'likes_count' => 25,
                    'comments_count' => 7,
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 day')),
                    'is_public' => true
                ]
            ];
            
            foreach ($testPosts as $index => $post) {
                $result = $elastic->addPost($post);
                echo "<p>Добавлен пост: '{$post['title']}'</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>Ошибка Elasticsearch: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='warning'>Elasticsearch не доступен</p>";
    }
    
    // Redis инициализация
    echo "<h2>Инициализация Redis</h2>";
    if ($redis) {
        try {
            // Простой тест Redis
            $redis->setOnlineStatus('test_user', 'online', 10);
            $status = $redis->isUserOnline('test_user') ? 'online' : 'offline';
            echo "<p class='success'>Redis подключен и работает (тестовый статус: $status)</p>";
        } catch (Exception $e) {
            echo "<p class='error'>Ошибка Redis: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='warning'>Redis не доступен</p>";
    }
    
    // ClickHouse инициализация
    echo "<h2>Инициализация ClickHouse</h2>";
    if ($clickhouseAvailable && $clickhouse) {
        try {
            // Создаем таблицы
            $clickhouse->createUserAnalyticsTable();
            echo "<p class='success'>Таблица 'user_analytics' создана</p>";
            
            $clickhouse->createLikesTable();
            echo "<p class='success'>Таблица 'likes' создана</p>";
            
            // Добавляем тестовые данные
            $clickhouse->logUserActivity('1', 'init', 'Инициализация базы данных');
            echo "<p>Добавлена тестовая запись в аналитику</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>Ошибка ClickHouse: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='warning'>ClickHouse не доступен</p>";
    }
    
    echo "<h2 class='success'>✅ Инициализация завершена!</h2>";
    echo "<p><strong>Тестовые пользователи:</strong></p>";
    echo "<ul>";
    echo "<li><strong>ivan_petrov</strong> / demo123</li>";
    echo "<li><strong>anna_smirnova</strong> / demo123</li>";
    echo "</ul>";
    echo '<p><a href="index.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Перейти на главную</a></p>';
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px;'>";
    echo "<h3>Критическая ошибка:</h3>";
    echo "<p><strong>" . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

// Дополнительная диагностика
echo "<h2>Диагностика</h2>";
echo "<p>Проверка соединения с контейнерами...</p>";

$containers = [
    'elasticsearch' => 'http://elasticsearch:9200',
    'clickhouse' => 'http://clickhouse:8123'
];

foreach ($containers as $name => $url) {
    try {
        $client = new Client(['timeout' => 3]);
        $response = $client->get($url);
        echo "<p class='success'>✓ $name: доступен (HTTP {$response->getStatusCode()})</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ $name: недоступен (" . $e->getMessage() . ")</p>";
    }
}

echo "<h3>Рекомендации по устранению проблем:</h3>";
echo "<ol>";
echo "<li>Убедитесь, что все контейнеры запущены: <code>docker-compose ps</code></li>";
echo "<li>Проверьте логи контейнеров: <code>docker-compose logs</code></li>";
echo "<li>Перезапустите контейнеры: <code>docker-compose restart</code></li>";
echo "<li>Пересоберите образы: <code>docker-compose up -d --build</code></li>";
echo "</ol>";