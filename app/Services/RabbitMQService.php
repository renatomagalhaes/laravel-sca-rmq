<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQService
{
    protected $connection;

    public $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(env('RABBITMQ_HOST'), env('RABBITMQ_PORT'), env('RABBITMQ_USER'), env('RABBITMQ_PASSWORD'));
        $this->channel = $this->connection->channel();
    }

    public function setup($exchange, $queue, $routingKey)
    {
        // Declare the main exchange
        $this->channel->exchange_declare($exchange, 'topic', false, true, false);

        // Declare the DLX exchange
        $this->channel->exchange_declare('dlx', 'topic', false, true, false);

        // Declare the Error exchange
        $this->channel->exchange_declare('error_exchange', 'topic', false, true, false);

        // Declare the main queue with DLX settings
        $args = new AMQPTable([
            'x-dead-letter-exchange' => 'dlx',
            'x-dead-letter-routing-key' => 'dlq.'.$routingKey,
        ]);
        $this->channel->queue_declare($queue, false, true, false, false, false, $args);
        $this->channel->queue_bind($queue, $exchange, $routingKey);

        // Declare the DLQ queue
        $dlqQueue = 'dlq.'.$queue;
        $dlqRoutingKey = 'dlq.'.$routingKey;
        $this->channel->queue_declare($dlqQueue, false, true, false, false);
        $this->channel->queue_bind($dlqQueue, 'dlx', $dlqRoutingKey);

        // Declare the error queue
        $errorQueue = 'error.'.$queue;
        $errorRoutingKey = 'error.'.$routingKey;
        $this->channel->queue_declare($errorQueue, false, true, false, false);
        $this->channel->queue_bind($errorQueue, 'error_exchange', $errorRoutingKey);
    }

    public function consume($exchange, $queue, $routingKey, $callback)
    {
        $this->setup($exchange, $queue, $routingKey);
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function reprocess($dlq, $callback)
    {
        $this->channel->basic_consume($dlq, '', false, false, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function publish($exchange, $routingKey, $data, $headers = [])
    {
        $this->channel->exchange_declare($exchange, 'topic', false, true, false);
        $msg = new AMQPMessage(json_encode($data), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable($headers),
        ]);
        $this->channel->basic_publish($msg, $exchange, $routingKey);
    }

    public function moveToErrorQueue($message, $routingKey)
    {
        $newMessage = new AMQPMessage($message->body, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => $message->get('application_headers'),
        ]);
        $this->channel->basic_publish($newMessage, 'error_exchange', 'error.'.$routingKey);
        $message->ack();
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
