<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'fullname',
        'email',
        'password',
        'date_of_birth',
        'gender',
        // Add any other relevant fields
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}