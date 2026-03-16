<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class Customer extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = 'customers';
    protected $fillable = [
        'id',
        'name', 
        'email', 
        'customer_id', 
        'password',
        'otp', 
        'google_id', 
        'profile_img', 
        'phone_number', 
        'status',
        'date_of_birth',
        'gender',
        'bio',
        'login_attempts',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
    ];
    
    protected $casts = [
        'status' => 'boolean',
    ];
  
    
    
    
}
