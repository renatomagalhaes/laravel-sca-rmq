<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesOrder;
use App\Models\User;

class SalesOrdersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::first();

        SalesOrder::create([
            'order_id' => 1,
            'customer_id' => $user->id,
            'payment_method' => 'credit_card',
            'paid_price' => 100.50,
            'shipping_amount' => 10.00,
            'discount_value' => 5.00,
            'created_at' => now(),
            'status' => 'exportable'
        ]);
    }
}
