<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArrangementPrice extends Model
{
    protected $fillable = ['type', 'name', 'price', 'active'];
}