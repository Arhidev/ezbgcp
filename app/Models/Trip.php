<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function route()
    {
        return $this->belongsTo(Route::class)->withTrashed();
    }

    public function tripDetails()
    {
        return $this->hasMany(TripDetail::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function suspensions()
    {
        return $this->hasMany(SuspendedTrip::class);
    }

}
