<?php

namespace App\Console\Commands;

use App\Consumers\SalesOrderConsumer;
use Illuminate\Console\Command;

class ConsumeSalesOrder extends Command
{
    protected $signature = 'consume:sales_order';

    protected $description = 'Consume messages from the sales_order_queue';

    protected $consumer;

    public function __construct(SalesOrderConsumer $consumer)
    {
        parent::__construct();
        $this->consumer = $consumer;
    }

    public function handle()
    {
        $this->info('Starting consumer for sales_order_queue');
        $this->consumer->listen();
    }
}
