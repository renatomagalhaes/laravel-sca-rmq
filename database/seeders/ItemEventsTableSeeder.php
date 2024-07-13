<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemEvent;
use App\Models\SalesOrder;
use App\Models\User;

class ItemEventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $salesOrder = SalesOrder::first();
        $user = User::first();

        ItemEvent::create([
            'item_id' => 1,
            'order_id' => $salesOrder->order_id,
            'customer_id' => $user->id,
            'event_type' => 'created',
            'price' => 50.00,
            'quantity' => 2,
            'status' => 'exportable'
        ]);
        
        sleep(1);

        ItemEvent::create([
          'item_id' => 1,
          'order_id' => $salesOrder->order_id,
          'customer_id' => $user->id,
          'event_type' => 'created',
          'price' => 50.00,
          'quantity' => 2,
          'status' => 'exported'
      ]);
    }
}
