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
        Schema::create('item_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('event_type');
            $table->decimal('price', 8, 2);
            $table->integer('quantity');
            $table->string('status');
            $table->timestamps();

            $table->index('item_id');
            $table->index('order_id');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_events');
    }
};
