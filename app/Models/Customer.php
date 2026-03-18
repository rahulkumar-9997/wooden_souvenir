<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'customers';
    protected $fillable = [
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
        'otp',
    ];
    protected $casts = [
        'status' => 'boolean',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'login_attempts' => 'integer'
    ];

    public function generateOtp()
    {
        $otp = rand(100000, 999999); 
        $this->otp = $otp;
        $this->save();
        cache()->put('otp_' . $this->id, $otp, now()->addMinutes(10));        
        return $otp;
    }

    
    public function verifyOtp($otp)
    {
        $cachedOtp = cache()->get('otp_' . $this->id);        
        if ($cachedOtp && $cachedOtp == $otp) {
            $this->otp = null;
            $this->save();
            cache()->forget('otp_' . $this->id);
            return true;
        }
        return false;
    }

    public static function generateCustomerId()
    {
        $prefix = 'CUST';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(uniqid(), -4));
        $customerId = $prefix . $timestamp . $random;
        while (self::where('customer_id', $customerId)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $customerId = $prefix . $timestamp . $random;
        }
        
        return $customerId;
    }

    
    public function isActive()
    {
        return $this->status == 1;
    }

    public function incrementLoginAttempts()
    {
        $this->increment('login_attempts');
    }

    public function resetLoginAttempts()
    {
        $this->update(['login_attempts' => 0]);
    }

    public function isLocked()
    {
        return $this->login_attempts >= 5;
    }
}