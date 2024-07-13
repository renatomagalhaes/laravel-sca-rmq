<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'order_id',
        'customer_id',
        'event_type',
        'price',
        'quantity',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
