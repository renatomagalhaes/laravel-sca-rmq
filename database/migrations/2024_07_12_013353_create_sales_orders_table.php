<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->string('payment_method');
            $table->decimal('paid_price', 10, 2);
            $table->decimal('shipping_amount', 10, 2);
            $table->decimal('discount_value', 10, 2);
            $table->timestamp('order_created_at')->useCurrent();
            $table->enum('status', ['exportable', 'exported', 'invoice_generated', 'delivered', 'returned', 'fraud']);
            $table->timestamps();

            $table->index('order_id');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
