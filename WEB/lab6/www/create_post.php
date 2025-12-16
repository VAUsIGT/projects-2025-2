<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $hashtags = $_POST['hashtags'] ?? '';
    
    if (empty($title) || empty($content)) {
        $error = 'Заполните заголовок и содержимое поста';
    } else {
        try {
            $hashtagsArray = !empty($hashtags) ? 
                array_map('trim', explode(',', $hashtags)) : [];
            
            $postData = [
                'user_id' => $currentUser['id'],
                'username' => $currentUser['username'],
                'title' => $title,
                'content' => $content,
                'hashtags' => $hashtagsArray,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => date('Y-m-d\TH:i:s'),
                'is_public' => true
            ];
            
            $result = $elastic->addPost($postData);
            
            if (isset($result['_id'])) {
                $success = 'Пост успешно опубликован!';
                
                // Логируем создание поста
                $clickhouse->logUserActivity($currentUser['id'], 'create_post', 
                    'Создан пост: ' . substr($title, 0, 50));
                
                // Очищаем кеш ленты
                $redis->cacheUserFeed($currentUser['id'], null);
                
                // Перенаправляем на главную через 2 секунды
                header("Refresh: 2; URL=index.php");
            } else {
                $error = 'Ошибка при публикации поста';
            }
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый пост - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo"><?= SITE_NAME ?></a>
            <div class="nav-links">
                <span>Привет, <?= escape($currentUser['username']) ?>!</span>
                <a href="index.php">Лента</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <header class="page-header">
            <h1>Создание нового поста</h1>
            <a href="index.php" class="btn btn-secondary">Назад к ленте</a>
        </header>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= escape($success) ?></div>
            <p>Вы будете перенаправлены на главную страницу...</p>
        <?php else: ?>
            <div class="create-post-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?= escape($_POST['title'] ?? '') ?>"
                               placeholder="О чем этот пост?">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Содержимое *</label>
                        <textarea id="content" name="content" rows="10" required
                                  placeholder="Поделитесь своими мыслями..."><?= escape($_POST['content'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="hashtags">Хештеги</label>
                        <input type="text" id="hashtags" name="hashtags" 
                               value="<?= escape($_POST['hashtags'] ?? '') ?>"
                               placeholder="php, programming, web (через запятую)">
                        <small class="form-hint">Укажите хештеги через запятую</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Опубликовать</button>
                        <a href="index.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>