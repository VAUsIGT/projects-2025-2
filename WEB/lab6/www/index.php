<?php
require_once 'config.php';

$pageTitle = "Лента новостей";
$currentUser = getCurrentUser();

// Получение постов
$posts = [];
try {
    $postsResult = $elastic->searchPosts('', null);
    $posts = $postsResult['hits']['hits'] ?? [];
} catch (Exception $e) {
    $error = "Ошибка загрузки ленты: " . $e->getMessage();
}

// Кеширование ленты в Redis
if ($currentUser && !empty($posts)) {
    $redis->cacheUserFeed($currentUser['id'], $posts);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo"><?= SITE_NAME ?></a>
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <span>Привет, <?= escape($currentUser['username']) ?>!</span>
                    <a href="create_post.php">Новый пост</a>
                    <a href="profile.php">Профиль</a>
                    <a href="search.php">Поиск</a>
                    <a href="logout.php">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <header class="page-header">
            <h1><?= escape($pageTitle) ?></h1>
            <?php if (isLoggedIn()): ?>
                <a href="create_post.php" class="btn btn-primary">Создать пост</a>
            <?php endif; ?>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>

        <div class="feed">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <h3>Пока нет постов</h3>
                    <p>Будьте первым, кто поделится чем-то интересным!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): 
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
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> <?= SITE_NAME ?>. Лабораторная работа №6.</p>
            <p>Elasticsearch + Redis + ClickHouse</p>
        </div>
    </footer>
</body>
</html>