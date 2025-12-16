<?php
require_once 'vendor/autoload.php';

use App\ElasticExample;
use App\RedisExample;
use App\ClickhouseExample;

// Инициализация сессии
session_start();

// Конфигурация
define('SITE_NAME', 'SimpleSocial');
define('SITE_URL', 'http://localhost:8080');

// Инициализация клиентов БД с обработкой ошибок
try {
    $elastic = new ElasticExample();
} catch (Exception $e) {
    error_log("Elasticsearch error: " . $e->getMessage());
    $elastic = null;
}

try {
    $redis = new RedisExample();
} catch (Exception $e) {
    error_log("Redis error: " . $e->getMessage());
    $redis = null;
}

try {
    $clickhouse = new ClickhouseExample();
} catch (Exception $e) {
    error_log("ClickHouse error: " . $e->getMessage());
    $clickhouse = null;
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Получение текущего пользователя
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Редирект
function redirect($url) {
    header("Location: $url");
    exit;
}

// Защита от XSS
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Генерация случайного ID
function generateId() {
    return bin2hex(random_bytes(16));
}