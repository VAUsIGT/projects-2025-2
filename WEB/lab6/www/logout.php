<?php
require_once 'config.php';

if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    
    // Пытаемся залогировать выход, но не падаем при ошибке
    if ($clickhouse && $currentUser) {
        try {
            $clickhouse->logUserActivity($currentUser['id'], 'logout', 'Выход из системы');
        } catch (Exception $e) {
            // Игнорируем ошибку логирования
        }
    }
    
    // Очищаем сессию
    $_SESSION = array();
    
    // Удаляем куки сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Перенаправляем на главную
redirect('index.php');