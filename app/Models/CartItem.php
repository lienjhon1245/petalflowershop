<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    //

    protected $fillable = [
        'user_id',
        'cart_id',
        'product_id',
        'name',        // Added name field
        'image',       // Add this line
        'quantity',
        'price_at_time_of_addition',
        'custom_message',
        'delivery_date',
        'delivery_location',
        'delivery_fee'
    ];

    // Relationship with Cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with DeliveryFee
    public function deliveryFee()
    {
        return $this->belongsTo(DeliveryFee::class);
    }
}
