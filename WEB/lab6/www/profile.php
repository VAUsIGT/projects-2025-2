<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$pageTitle = "Профиль: " . $currentUser['username'];

// Получаем посты пользователя
$userPosts = [];
try {
    if ($elastic) {
        // Используем поиск по username
        $query = [
            'query' => [
                'term' => [
                    'username' => $currentUser['username']
                ]
            ],
            'sort' => [
                ['created_at' => ['order' => 'desc']]
            ]
        ];
        
        $response = $elastic->client->get('posts/_search', [
            'json' => $query
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        $userPosts = $result['hits']['hits'] ?? [];
    }
} catch (Exception $e) {
    $error = "Ошибка загрузки постов: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= escape($pageTitle) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo"><?= SITE_NAME ?></a>
            <div class="nav-links">
                <span>Привет, <?= escape($currentUser['username']) ?>!</span>
                <a href="index.php">Лента</a>
                <a href="create_post.php">Новый пост</a>
                <a href="search.php">Поиск</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <header class="page-header">
            <h1><?= escape($pageTitle) ?></h1>
        </header>

        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-card">
                    <h3>Информация</h3>
                    <p><strong>Имя:</strong> <?= escape($currentUser['full_name'] ?? 'Не указано') ?></p>
                    <p><strong>Email:</strong> <?= escape($currentUser['email'] ?? 'Не указан') ?></p>
                    <p><strong>Город:</strong> <?= escape($currentUser['city'] ?? 'Не указан') ?></p>
                    <p><strong>Возраст:</strong> <?= escape($currentUser['age'] ?? 'Не указан') ?></p>
                    <p><strong>Интересы:</strong> <?= escape($currentUser['interests'] ?? 'Не указаны') ?></p>
                </div>
                
                <div class="profile-stats">
                    <h3>Статистика</h3>
                    <p>Постов: <?= count($userPosts) ?></p>
                    <?php if ($redis->isUserOnline($currentUser['id'])): ?>
                        <p><span class="online-status online"></span> В сети</p>
                    <?php else: ?>
                        <p><span class="online-status offline"></span> Не в сети</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-content">
                <h2>Мои посты</h2>
                
                <?php if (empty($userPosts)): ?>
                    <div class="empty-state">
                        <p>У вас еще нет постов</p>
                        <a href="create_post.php" class="btn btn-primary">Создать первый пост</a>
                    </div>
                <?php else: ?>
                    <div class="posts-list">
                        <?php foreach ($userPosts as $post): 
                            $postData = $post['_source'];
                        ?>
                            <article class="post">
                                <div class="post-header">
                                    <div class="post-author">
                                        <strong><?= escape($postData['username']) ?></strong>
                                        <small><?= date('d.m.Y H:i', strtotime($postData['created_at'])) ?></small>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <h3><?= escape($postData['title']) ?></h3>
                                    <p><?= nl2br(escape($postData['content'])) ?></p>
                                    <?php if (!empty($postData['hashtags'])): ?>
                                        <div class="hashtags">
                                            <?php foreach ($postData['hashtags'] as $tag): ?>
                                                <span class="hashtag">#<?= escape($tag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="post-footer">
                                    <span class="likes">❤️ <?= $postData['likes_count'] ?> лайков</span>
                                    <span class="comments">💬 <?= $postData['comments_count'] ?> комментариев</span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>