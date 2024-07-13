<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'payment_method',
        'paid_price',
        'shipping_amount',
        'discount_value',
        'created_at',
        'status',
    ];

    public function itemEvents()
    {
        return $this->hasMany(ItemEvent::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
