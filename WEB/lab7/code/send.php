<?php
require 'vendor/autoload.php';
require 'QueueManager.php';

$queueManager = new QueueManager();

// Определяем систему отправки
$system = isset($_POST['system']) ? $_POST['system'] : (rand(0, 1) ? 'rabbit' : 'kafka');

$messageData = [
    'id' => uniqid(),
    'name' => $_POST['name'] ?? 'Тестовое сообщение',
    'type' => $_POST['type'] ?? 'default',
    'system' => $system,
    'created_at' => date('Y-m-d H:i:s')
];

try {
    $queueManager->publish($messageData, $system, 'main');
    
    echo "✅ Сообщение отправлено в $system!\n";
    echo "📨 Данные: " . json_encode($messageData) . "\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка отправки: " . $e->getMessage() . "\n";
}