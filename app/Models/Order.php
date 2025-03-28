<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    protected $fillable = [
        'reference_number', // Add this line
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



    protected static function boot()
{
    parent::boot();
    
    static::creating(function ($order) {
        // Get the latest order
        $latestOrder = static::latest()->first();
        
        // If no order exists, start from PPS0000000001, else increment from the last order
        if (!$latestOrder) {
            $order->reference_number = 'PPS0000000001';
        } else {
            $lastNumber = intval(substr($latestOrder->reference_number, 3));
            $newNumber = str_pad($lastNumber + 1, 10, '0', STR_PAD_LEFT);
            $order->reference_number = 'PPS' . $newNumber;
        }
    });
}
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