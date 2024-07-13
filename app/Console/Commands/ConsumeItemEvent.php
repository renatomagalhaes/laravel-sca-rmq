<?php

namespace App\Console\Commands;

use App\Consumers\ItemEventConsumer;
use Illuminate\Console\Command;

class ConsumeItemEvent extends Command
{
    protected $signature = 'consume:item_event';

    protected $description = 'Consume messages from the item_event_queue';

    protected $consumer;

    public function __construct(ItemEventConsumer $consumer)
    {
        parent::__construct();
        $this->consumer = $consumer;
    }

    public function handle()
    {
        $this->info('Starting consumer for item_event_queue');
        $this->consumer->listen();
    }
}
