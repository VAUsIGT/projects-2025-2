<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        try {
            // Ищем пользователя по username
            $searchResult = $elastic->searchUsersByInterests($username, null);
            $users = $searchResult['hits']['hits'] ?? [];
            
            $userFound = null;
            foreach ($users as $userHit) {
                $user = $userHit['_source'];
                if ($user['username'] === $username) {
                    $userFound = $user;
                    $userFound['id'] = $userHit['_id'];
                    break;
                }
            }
            
            if ($userFound) {
                // В реальном проекте здесь была бы проверка хеша пароля
                // Для демо просто сравниваем строки
                if ($password === 'demo123') { // Демо-пароль
                    $_SESSION['user_id'] = $userFound['id'];
                    $_SESSION['user'] = $userFound;
                    
                    // Устанавливаем онлайн статус
                    $redis->setOnlineStatus($userFound['id']);
                    
                    // Логируем вход
                    $clickhouse->logUserActivity($userFound['id'], 'login', 'Вход в систему');
                    
                    redirect('index.php');
                } else {
                    $error = 'Неверный пароль';
                }
            } else {
                $error = 'Пользователь не найден';
            }
        } catch (Exception $e) {
            $error = 'Ошибка входа: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Вход в <?= SITE_NAME ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= escape($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= escape($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                    <small class="form-hint">Демо-пароль: demo123</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>
            
            <div class="auth-links">
                <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
                <p><a href="index.php">Вернуться на главную</a></p>
            </div>
        </div>
    </div>
</body>
</html>