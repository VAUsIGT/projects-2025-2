<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    if (empty($username) || empty($email)) {
        $error = 'Заполните обязательные поля';
    } else {
        try {
            // Проверяем, нет ли уже такого пользователя
            $searchResult = $elastic->searchUsersByInterests($username, null);
            $users = $searchResult['hits']['hits'] ?? [];
            
            $userExists = false;
            foreach ($users as $userHit) {
                $user = $userHit['_source'];
                if ($user['username'] === $username || $user['email'] === $email) {
                    $userExists = true;
                    break;
                }
            }
            
            if ($userExists) {
                $error = 'Пользователь с таким именем или email уже существует';
            } else {
                // Создаем нового пользователя
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $fullName,
                    'bio' => $bio,
                    'city' => $_POST['city'] ?? '',
                    'age' => (int)($_POST['age'] ?? 0),
                    'interests' => $_POST['interests'] ?? '',
                    'created_at' => date('Y-m-d\TH:i:s')
                ];
                
                $result = $elastic->addUser($userData);
                
                if (isset($result['_id'])) {
                    $success = 'Регистрация успешна! Теперь вы можете войти.';
                    // Автоматически логиним пользователя
                    $_SESSION['user_id'] = $result['_id'];
                    $_SESSION['user'] = $userData;
                    $_SESSION['user']['id'] = $result['_id'];
                    
                    // Логируем регистрацию
                    $clickhouse->logUserActivity($result['_id'], 'register', 'Регистрация нового пользователя');
                    
                    redirect('index.php');
                } else {
                    $error = 'Ошибка при регистрации';
                }
            }
        } catch (Exception $e) {
            $error = 'Ошибка регистрации: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Регистрация в <?= SITE_NAME ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= escape($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= escape($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= escape($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Полное имя</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= escape($_POST['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">О себе</label>
                    <textarea id="bio" name="bio" rows="3"><?= escape($_POST['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="city">Город</label>
                    <input type="text" id="city" name="city" 
                           value="<?= escape($_POST['city'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="age">Возраст</label>
                    <input type="number" id="age" name="age" min="1" max="150" 
                           value="<?= escape($_POST['age'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="interests">Интересы</label>
                    <input type="text" id="interests" name="interests" 
                           value="<?= escape($_POST['interests'] ?? '') ?>"
                           placeholder="программирование, музыка, спорт...">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
            </form>
            
            <div class="auth-links">
                <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
                <p><a href="index.php">Вернуться на главную</a></p>
            </div>
        </div>
    </div>
</body>
</html>