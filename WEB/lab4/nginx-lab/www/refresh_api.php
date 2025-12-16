<?php
session_start();
header('Content-Type: application/json');

// Проверяем, что запрос пришел через AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

require_once 'ApiCache.php';

// Обработка очистки кеша
if (isset($_POST['clear_cache']) && $_POST['clear_cache'] === 'true') {
    ApiCache::clearCache();
    echo json_encode([
        'success' => true,
        'message' => 'Кеш успешно очищен'
    ]);
    exit();
}

// Получаем параметры
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 55.75;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 37.61;
$force = isset($_POST['force']) && $_POST['force'] === 'true';

// Формируем URL API
$url = "https://api.bigdatacloud.net/data/reverse-geocode-client?latitude={$latitude}&longitude={$longitude}&localityLanguage=ru";

$cacheKey = md5($url);
$response = [];

try {
    // Получаем данные (с принудительным обновлением если нужно)
    $apiData = ApiCache::get($url, $force);
    
    // Проверяем, вернул ли API ошибку
    if (isset($apiData['error'])) {
        // Пробуем получить данные из кеша, даже если они старые
        $allCache = ApiCache::getAllCache();
        if (isset($allCache[$cacheKey])) {
            $apiData = $allCache[$cacheKey]['data'];
            $response['using_cached'] = true;
            $response['cache_age'] = time() - $allCache[$cacheKey]['timestamp'];
        }
    }
    
    // Сохраняем в сессию для последующих запросов
    $_SESSION['api_data'] = $apiData;
    $_SESSION['api_cache_info'] = ApiCache::getCacheInfo($cacheKey);
    
    // Подготавливаем ответ
    $response['success'] = !isset($apiData['error']);
    $response['data'] = $apiData;
    $response['cache_info'] = [
        'timestamp' => $_SESSION['api_cache_info']['timestamp'] ?? null,
        'age' => $_SESSION['api_cache_info']['age'] ?? 0,
        'remaining' => $_SESSION['api_cache_info']['remaining'] ?? 0,
        'is_valid' => $_SESSION['api_cache_info']['is_valid'] ?? false,
        'next_update' => date('H:i:s', time() + ($_SESSION['api_cache_info']['remaining'] ?? 300))
    ];
    
    // Если в данных API есть ошибка
    if (isset($apiData['error'])) {
        $response['error'] = $apiData['error'];
        $response['message'] = 'Ошибка API: ' . $apiData['error'];
    }
    
} catch (Exception $e) {
    // Пробуем получить последние данные из кеша при ошибке
    $allCache = ApiCache::getAllCache();
    if (isset($allCache[$cacheKey])) {
        $response['success'] = true;
        $response['data'] = $allCache[$cacheKey]['data'];
        $response['cache_info'] = [
            'timestamp' => $allCache[$cacheKey]['timestamp'],
            'age' => time() - $allCache[$cacheKey]['timestamp'],
            'remaining' => 0,
            'is_valid' => false,
            'next_update' => 'требуется обновление'
        ];
        $response['using_cached'] = true;
        $response['warning'] = 'Используются устаревшие данные из кеша (ошибка при запросе к API)';
    } else {
        $response = [
            'success' => false,
            'error' => 'Ошибка при получении данных: ' . $e->getMessage(),
            'message' => 'Не удалось получить данные API и нет сохраненного кеша'
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();