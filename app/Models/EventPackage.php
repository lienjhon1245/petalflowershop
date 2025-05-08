<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPackage extends Model
{
    protected $fillable = ['type', 'name', 'price', 'active'];

    protected $casts = [
        'price' => 'decimal:2'
    ];
}