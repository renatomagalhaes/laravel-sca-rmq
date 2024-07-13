<?php

namespace App\Console\Commands;

use App\Services\RabbitMQService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ReprocessDlq extends Command
{
    protected $signature = 'dlq:reprocess {queue}';

    protected $description = 'Reprocess messages from the DLQ';

    protected $queueRoutingKeyMap = [
        'sales_order_queue' => 'sales_order',
        'item_event_queue' => 'item_event',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $queue = $this->argument('queue');
        $dlq = 'dlq.'.$queue;

        $rabbitMQService = app(RabbitMQService::class);

        if (! array_key_exists($queue, $this->queueRoutingKeyMap)) {
            $this->error("No routing key found for queue: $queue");

            return;
        }

        $routingKey = $this->queueRoutingKeyMap[$queue];

        // Verifica e configura as filas DLQ e de erro se não existirem
        $this->ensureQueuesExist($rabbitMQService, $queue, $dlq, $routingKey);

        $rabbitMQService->reprocess($dlq, function (AMQPMessage $message) use ($rabbitMQService, $routingKey) {
            $this->reprocessMessage($message, $rabbitMQService, $routingKey);
        });
    }

    protected function reprocessMessage(AMQPMessage $message, $rabbitMQService, $routingKey)
    {
        $headers = $message->has('application_headers') ? $message->get('application_headers')->getNativeData() : [];
        $dlqRetryCount = isset($headers['x-dlq-retries']) ? $headers['x-dlq-retries'] + 1 : 1;
        $headers['x-dlq-retries'] = $dlqRetryCount;

        unset($headers['x-death']);
        unset($headers['x-retries']);

        if ($dlqRetryCount > 5) {
            $rabbitMQService->moveToErrorQueue($message, $routingKey);
            $this->info('Message moved to error queue after exceeding max DLQ retries');

            return;
        }

        $newMessage = new AMQPMessage($message->body, [
            'delivery_mode' => 2, // make message persistent
            'application_headers' => new AMQPTable($headers),
        ]);

        $originalRoutingKey = $headers['x-original-routing-key'] ?? $routingKey;

        Log::info('Reprocessing message', ['dlqRetryCount' => $dlqRetryCount, 'originalRoutingKey' => $originalRoutingKey]);

        $rabbitMQService->publish('topic_exchange', $originalRoutingKey, json_decode($newMessage->body, true), $headers);
        $message->ack();
        $this->info("Reprocessed message from DLQ to queue with routing key: {$originalRoutingKey}");
    }

    protected function ensureQueuesExist($rabbitMQService, $queue, $dlq, $routingKey)
    {
        try {
            // Verifica se a fila DLQ existe
            $rabbitMQService->channel->queue_declare($dlq, false, true, false, false);
            $rabbitMQService->channel->queue_bind($dlq, 'dlx', 'dlq.'.$routingKey);

            // Verifica se a fila de erro existe
            $errorQueue = 'error.'.$queue;
            $rabbitMQService->channel->queue_declare($errorQueue, false, true, false, false);
            $rabbitMQService->channel->queue_bind($errorQueue, 'error_exchange', 'error.'.$routingKey);
        } catch (AMQPProtocolChannelException $e) {
            // Se as filas não existirem, configure-as corretamente
            if ($e->getCode() === 404) {
                $this->setupQueues($rabbitMQService, $queue, $routingKey);
            } else {
                throw $e;
            }
        }
    }

    protected function setupQueues($rabbitMQService, $queue, $routingKey)
    {
        // Configura a fila DLQ
        $dlqQueue = 'dlq.'.$queue;
        $dlqRoutingKey = 'dlq.'.$routingKey;
        $rabbitMQService->channel->queue_declare($dlqQueue, false, true, false, false);
        $rabbitMQService->channel->queue_bind($dlqQueue, 'dlx', $dlqRoutingKey);

        // Configura a fila de erro
        $errorQueue = 'error.'.$queue;
        $errorRoutingKey = 'error.'.$routingKey;
        $rabbitMQService->channel->queue_declare($errorQueue, false, true, false, false);
        $rabbitMQService->channel->queue_bind($errorQueue, 'error_exchange', $errorRoutingKey);
    }
}
