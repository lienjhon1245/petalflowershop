<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'cart_id',
        'product_id',
        'name',        
        'image',       
        'quantity',
        'price_at_time_of_addition',
        'custom_message',
        'delivery_date',
        'delivery_location',
        'delivery_fee',
        'customization_id',
        'price',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
        'price' => 'decimal:2',
        'price_at_time_of_addition' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'delivery_date' => 'datetime'
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
        return $this->belongsTo(DeliveryFee::class, 'delivery_fee');
    }

    // Helper method to check if item is a custom arrangement
    public function isCustomArrangement()
    {
        return !empty($this->customization_id);
    }

    // Get total price including delivery fee
    public function getTotalPrice()
    {
        return $this->price_at_time_of_addition * $this->quantity + ($this->delivery_fee ?? 0);
    }

    // Add this method at the end of your existing CartItem class
    public function getCustomization()
    {
        return $this->details['customization'] ?? null;
    }
}
