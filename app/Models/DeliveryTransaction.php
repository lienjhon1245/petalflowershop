<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_man_id',
        'status',
        'timestamp',
        'location',
        'notes'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryMan()
    {
        return $this->belongsTo(User::class, 'delivery_man_id');
    }
}