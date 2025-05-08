<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'image',
        'quantity',
        'price',
        'total_price',
        'custom_message',
        'delivery_date',
        'delivery_location',
        'delivery_fee',
        'details'  // Added details field
    ];

    protected $casts = [
        'details' => 'array',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'delivery_date' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Order::class, 'id', 'id', 'order_id', 'user_id');
    }
}
