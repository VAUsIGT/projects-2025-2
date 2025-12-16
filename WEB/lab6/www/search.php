<?php
require_once 'config.php';

$pageTitle = "Поиск";
$currentUser = getCurrentUser();
$searchQuery = $_GET['q'] ?? '';
$searchType = $_GET['type'] ?? 'posts';
$results = [];
$error = '';

if (!empty($searchQuery)) {
    try {
        if ($searchType === 'users') {
            $searchResult = $elastic->searchUsersByInterests($searchQuery, null);
            $results = $searchResult['hits']['hits'] ?? [];
        } else {
            $searchResult = $elastic->searchPosts($searchQuery, null);
            $results = $searchResult['hits']['hits'] ?? [];
        }
        
        // Логируем поиск
        if ($currentUser) {
            $clickhouse->logUserActivity($currentUser['id'], 'search', 
                "Поиск: $searchQuery (тип: $searchType)");
        }
    } catch (Exception $e) {
        $error = "Ошибка поиска: " . $e->getMessage();
    }
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
                <?php if (isLoggedIn()): ?>
                    <span>Привет, <?= escape($currentUser['username']) ?>!</span>
                    <a href="index.php">Лента</a>
                    <a href="create_post.php">Новый пост</a>
                    <a href="profile.php">Профиль</a>
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
        </header>

        <div class="search-form">
            <form method="GET" action="search.php">
                <div class="search-input-group">
                    <input type="text" name="q" 
                           value="<?= escape($searchQuery) ?>"
                           placeholder="Введите запрос для поиска..."
                           required>
                    <select name="type">
                        <option value="posts" <?= $searchType === 'posts' ? 'selected' : '' ?>>Посты</option>
                        <option value="users" <?= $searchType === 'users' ? 'selected' : '' ?>>Пользователи</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Искать</button>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($searchQuery)): ?>
            <div class="search-results">
                <h2>Результаты поиска для "<?= escape($searchQuery) ?>"</h2>
                <p>Найдено: <?= count($results) ?></p>
                
                <?php if (empty($results)): ?>
                    <div class="empty-state">
                        <p>По вашему запросу ничего не найдено</p>
                    </div>
                <?php elseif ($searchType === 'users'): ?>
                    <div class="users-list">
                        <?php foreach ($results as $user): 
                            $userData = $user['_source'];
                        ?>
                            <div class="user-card">
                                <h3><?= escape($userData['username']) ?></h3>
                                <p><?= escape($userData['full_name'] ?? '') ?></p>
                                <p><?= escape($userData['bio'] ?? '') ?></p>
                                <p><small>Город: <?= escape($userData['city'] ?? 'Не указан') ?></small></p>
                                <p><small>Интересы: <?= escape($userData['interests'] ?? '') ?></small></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="posts-list">
                        <?php foreach ($results as $post): 
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
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>