<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    protected $fillable = [
        'name',       // Added the new name field
        'user_id',
        'customer_id',
        'delivery_man_id',
        'status',
        'total_amount',
        'price',       // Add this line
        'delivery_address',
        'contact_number',
        'payment_method',
        'payment_status',
        'delivery_date',
        'notes',
        'image',       // Add this line
        'proof',       // Add this line
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function deliveryMan()
    {
        return $this->belongsTo(User::class, 'delivery_man_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(DeliveryTransaction::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}