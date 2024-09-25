<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'tel_number',
        'wallet',
        'status_id',
        'role',
        'uid',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
      return $this->is_admin;
    }

    public function messages()
    {
      return $this->hasMany(Message::class);
    }

    public function getMustVerifyEmailAttribute()
    {
        return config('auth.must_verify_email');
    }

    public function trips() 
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }
    //bus
    public function bus()
    {
        return $this->hasOne(Bus::class, 'driver_id');
    }
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class)->orderBy('created_at', 'desc')->where('seen',0);
    }

    public function favoritePlaces()
    {
        return $this->hasMany(Place::class)->where('favorite', 1);
    }

    public function recentPlaces()
    {
        return $this->hasMany(Place::class)->where('favorite', 0);
    }

    //reservations
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    //payments
    public function charges()
    {
        return $this->hasMany(UserCharge::class, 'user_id');
    }

    //bank account
    public function bankAccount()
    {
        //return the last saved bank account
        return $this->hasOne(BankAccount::class, 'user_id')->latest();
    }

    //mobile money
    public function mobileMoneyAccount()
    {
        return $this->hasOne(MobileMoneyAccount::class, 'user_id')->latest();
    }

    //paypal
    public function paypalAccount()
    {
        return $this->hasOne(PaypalAccount::class, 'user_id')->latest();
    }

    //driver information
    public function driverInformation()
    {
        return $this->hasOne(DriverInformation::class, 'user_id');
    }

}
