<?php
echo "Лабораторная работа №7 работает!<br>";
echo "PHP версия: " . phpversion() . "<br>";

// Проверяем наличие RabbitMQ расширения
if (class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
    echo "✅ RabbitMQ библиотека доступна<br>";
} else {
    echo "❌ RabbitMQ библиотека не найдена<br>";
}

// Проверяем наличие Kafka расширения
if (class_exists('Kafka\Producer')) {
    echo "✅ Kafka библиотека доступна<br>";
} else {
    echo "❌ Kafka библиотека не найдена<br>";
}

// Проверяем подключение к RabbitMQ
echo "<br>Проверка подключения к RabbitMQ:<br>";
try {
    $connection = new PhpAmqpLib\Connection\AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    echo "✅ Подключение к RabbitMQ успешно<br>";
    $connection->close();
} catch (Exception $e) {
    echo "❌ Ошибка подключения к RabbitMQ: " . $e->getMessage() . "<br>";
}

// Меню
echo "<br><h3>Меню:</h3>";
echo "<a href='/send.php'>Отправить сообщение</a><br>";
echo "<a href='http://localhost:15672' target='_blank'>Панель управления RabbitMQ</a><br>";
?>