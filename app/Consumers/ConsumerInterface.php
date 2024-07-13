<?php

namespace App\Consumers;

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    public function listen(): void;

    public function processMessage(AMQPMessage $message): void;
}
