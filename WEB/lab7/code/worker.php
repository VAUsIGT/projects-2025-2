<?php
require 'vendor/autoload.php';
require 'QueueManager.php';

class MessageProcessor {
    private $queueManager;
    private $maxRetries = 3;

    public function __construct() {
        $this->queueManager = new QueueManager();
    }

    public function processMessage($data, $system = 'rabbit') {
        echo "🔧 Обработка сообщения из $system: " . json_encode($data) . "\n";
        
        try {
            $this->simulateProcessing($data);
            
            // Успешная обработка
            $this->logSuccess($data, $system);
            echo "✅ Успешно обработано\n";
            
        } catch (Exception $e) {
            echo "❌ Ошибка обработки: " . $e->getMessage() . "\n";
            
            // Перемещаем в очередь ошибок
            $this->queueManager->moveToErrorQueue($data, $system);
            echo "📤 Перемещено в очередь ошибок ($system)\n";
            
            $this->logError($data, $e->getMessage(), $system);
        }
    }

    private function simulateProcessing($data) {
        sleep(1);
        
        // ошибки для демонстрации
        if (rand(1, 100) <= 30) {
            $errors = [
                "Ошибка базы данных",
                "Таймаут соединения", 
                "Невалидные данные",
                "Ошибка сети"
            ];
            throw new Exception($errors[array_rand($errors)]);
        }
    }

    private function logSuccess($data, $system) {
        $logEntry = [
            'status' => 'success',
            'system' => $system,
            'data' => $data,
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents('processing.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }

    private function logError($data, $error, $system) {
        $logEntry = [
            'status' => 'error',
            'system' => $system,
            'data' => $data,
            'error' => $error,
            'failed_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents('error.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }
}

// Запуск воркеров
$processor = new MessageProcessor();

echo "🚀 Запуск системы обработки...\n";
echo "📊 Используются обе системы: RabbitMQ + Kafka\n\n";

// Обработчик для RabbitMQ
$rabbitWorker = function() use ($processor) {
    echo "🐇 RabbitMQ Worker запущен (основная очередь)...\n";
    $qm = new QueueManager();
    $qm->consumeFromRabbit('main', function($data) use ($processor) {
        $processor->processMessage($data['data'], 'rabbit');
    });
};

// Обработчик для Kafka
$kafkaWorker = function() use ($processor) {
    echo "🦊 Kafka Worker запущен (основной топик)...\n";
    $qm = new QueueManager();
    $qm->consumeFromKafka('main', function($data) use ($processor) {
        $processor->processMessage($data['data'], 'kafka');
    });
};

// Запуск в фоновом режиме
if (isset($argv[1])) {
    switch ($argv[1]) {
        case 'rabbit':
            $rabbitWorker();
            break;
        case 'kafka':
            $kafkaWorker();
            break;
        default:
            echo "Использование: php worker.php [rabbit|kafka]\n";
    }
} else {
    // Запуск обоих воркеров
    $pid = pcntl_fork();
    
    if ($pid == -1) {
        die("Не удалось создать процесс");
    } elseif ($pid) {
        // Родительский процесс - RabbitMQ
        $rabbitWorker();
    } else {
        // Дочерний процесс - Kafka
        $kafkaWorker();
    }
}