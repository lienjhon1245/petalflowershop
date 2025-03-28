<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    //
    protected $fillable = [
        'user_id',
        
        'status'
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with CartItems
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
