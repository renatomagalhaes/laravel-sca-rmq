<?php

namespace App\Consumers;

use App\Models\ItemEvent;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ItemEventConsumer
{
    protected $rabbitMQService;

    protected $maxRetries = 10;

    protected $maxDlqRetries = 5;

    protected $exchange = 'topic_exchange';

    protected $queue = 'item_event_queue';

    protected $routingKey = 'item_event';

    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }

    public function listen()
    {
        $this->rabbitMQService->consume($this->exchange, $this->queue, $this->routingKey, [$this, 'processMessage']);
    }

    public function processMessage(AMQPMessage $message)
    {
        Log::info('Received message', ['message' => $message->body]);

        $data = json_decode($message->body, true);
        Log::info('Decoded message', ['data' => $data]);

        $retryCount = 0;
        $originalRoutingKey = $this->routingKey;
        if ($message->has('application_headers')) {
            $headers = $message->get('application_headers')->getNativeData();
            if (isset($headers['x-retries'])) {
                $retryCount = $headers['x-retries'];
            }
            if (isset($headers['x-original-routing-key'])) {
                $originalRoutingKey = $headers['x-original-routing-key'];
            }
        }

        Log::info('Processing message', ['retryCount' => $retryCount, 'originalRoutingKey' => $originalRoutingKey]);

        if ($retryCount >= $this->maxRetries) {
            $this->moveToDlq($message, $originalRoutingKey);

            return;
        }

        try {
            DB::beginTransaction();
            ItemEvent::create($data);
            DB::commit();
            $message->ack();
            Log::info('Item event processed successfully', ['data' => $data]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process item event', [
                'error' => $e->getMessage(),
                'data' => $data,
                'message' => $message->body,
            ]);
            $this->handleNack($message, $retryCount + 1, $originalRoutingKey);
        }
    }

    protected function handleNack(AMQPMessage $message, $retryCount, $originalRoutingKey)
    {
        try {
            $headers = $message->has('application_headers') ? $message->get('application_headers')->getNativeData() : [];
            $headers['x-retries'] = $retryCount;
            $headers['x-original-routing-key'] = $originalRoutingKey;

            $newMessage = new AMQPMessage($message->body, [
                'delivery_mode' => 2, // make message persistent
                'application_headers' => new AMQPTable($headers),
            ]);

            Log::info('Nacking message', ['retryCount' => $retryCount, 'originalRoutingKey' => $originalRoutingKey]);

            $message->delivery_info['channel']->basic_publish(
                $newMessage,
                $this->exchange,
                $originalRoutingKey
            );
            $message->ack();
            Log::info('Message nacked and requeued', ['retryCount' => $retryCount]);
        } catch (\Exception $e) {
            Log::error('Failed to nack the message', ['error' => $e->getMessage()]);
        }
    }

    protected function moveToDlq(AMQPMessage $message, $originalRoutingKey)
    {
        try {
            $headers = $message->has('application_headers') ? $message->get('application_headers')->getNativeData() : [];
            $dlqRetryCount = isset($headers['x-dlq-retries']) ? $headers['x-dlq-retries'] + 1 : 1;
            $headers['x-dlq-retries'] = $dlqRetryCount;
            $headers['x-original-routing-key'] = $originalRoutingKey;

            if ($dlqRetryCount > $this->maxDlqRetries) {
                $this->rabbitMQService->moveToErrorQueue($message, $originalRoutingKey);
                Log::info('Message moved to error queue', ['retryCount' => $dlqRetryCount]);

                return;
            }

            $newMessage = new AMQPMessage($message->body, [
                'delivery_mode' => 2, // make message persistent
                'application_headers' => new AMQPTable($headers),
            ]);

            Log::info('Moving message to DLQ', ['dlqRetryCount' => $dlqRetryCount, 'originalRoutingKey' => $originalRoutingKey]);

            $message->delivery_info['channel']->basic_publish(
                $newMessage,
                'dlx',
                'dlq.'.$originalRoutingKey
            );
            $message->ack();
            Log::info('Message moved to DLQ');
        } catch (\Exception $e) {
            Log::error('Failed to move message to DLQ', ['error' => $e->getMessage()]);
        }
    }
}
