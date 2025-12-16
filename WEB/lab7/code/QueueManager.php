<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Kafka\Producer;
use Kafka\ProducerConfig;
use Kafka\Consumer;
use Kafka\ConsumerConfig;

class QueueManager {
    private $rabbitChannel;
    private $kafkaConfig;
    
    // Очереди
    private $mainQueue = 'lab7_main_queue';
    private $errorQueue = 'lab7_error_queue';
    private $mainTopic = 'lab7_main_topic';
    private $errorTopic = 'lab7_error_topic';

    public function __construct() {
        // RabbitMQ connection
        $rabbitConnection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $this->rabbitChannel = $rabbitConnection->channel();
        
        // Declare queues
        $this->rabbitChannel->queue_declare($this->mainQueue, false, true, false, false);
        $this->rabbitChannel->queue_declare($this->errorQueue, false, true, false, false);
    }

    // === RABBITMQ METHODS ===
    public function publishToRabbit($data, $queueType = 'main') {
        $queueName = $queueType === 'error' ? $this->errorQueue : $this->mainQueue;
        
        $msg = new AMQPMessage(json_encode([
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'queue_type' => $queueType
        ]), ['delivery_mode' => 2]);
        
        $this->rabbitChannel->basic_publish($msg, '', $queueName);
    }

    public function consumeFromRabbit($queueType, callable $callback) {
        $queueName = $queueType === 'error' ? $this->errorQueue : $this->mainQueue;
        
        $this->rabbitChannel->basic_consume($queueName, '', false, true, false, false, 
            function($msg) use ($callback) {
                $data = json_decode($msg->body, true);
                $callback($data);
            }
        );

        while($this->rabbitChannel->is_consuming()) {
            $this->rabbitChannel->wait();
        }
    }

    public function getRabbitStats() {
        try {
            $mainStats = $this->rabbitChannel->queue_declare($this->mainQueue, true);
            $errorStats = $this->rabbitChannel->queue_declare($this->errorQueue, true);
            
            return [
                'main' => $mainStats[1] ?? 0,
                'error' => $errorStats[1] ?? 0
            ];
        } catch (Exception $e) {
            return ['main' => 0, 'error' => 0];
        }
    }

    // === KAFKA METHODS ===
    public function publishToKafka($data, $topicType = 'main') {
        $topicName = $topicType === 'error' ? $this->errorTopic : $this->mainTopic;

        $config = ProducerConfig::getInstance();
        $config->setMetadataBrokerList('kafka:9092');

        $producer = new Producer(function() use ($data, $topicName) {
            return [[
                'topic' => $topicName,
                'value' => json_encode([
                    'data' => $data,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'topic_type' => strpos($topicName, 'error') !== false ? 'error' : 'main'
                ]),
                'key' => uniqid(),
            ]];
        });

        $producer->send(true);
    }

    public function consumeFromKafka($topicType, callable $callback) {
        $topicName = $topicType === 'error' ? $this->errorTopic : $this->mainTopic;

        $config = ConsumerConfig::getInstance();
        $config->setMetadataBrokerList('kafka:9092');
        $config->setGroupId('lab7_group_' . $topicType);
        $config->setTopics([$topicName]);
        $config->setOffsetReset('earliest');

        $consumer = new Consumer();
        $consumer->start(function($topic, $part, $message) use ($callback) {
            $data = json_decode($message['message']['value'], true);
            $callback($data);
        });
    }

    // Универсальные методы
    public function publish($data, $system = 'rabbit', $type = 'main') {
        if ($system === 'kafka') {
            $this->publishToKafka($data, $type);
        } else {
            $this->publishToRabbit($data, $type);
        }
    }

    public function moveToErrorQueue($data, $originalSystem = 'rabbit') {
        $this->publish($data, $originalSystem, 'error');
    }

    public function __destruct() {
        if ($this->rabbitChannel) {
            $this->rabbitChannel->close();
        }
    }
}