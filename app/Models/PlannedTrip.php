<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlannedTrip extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];


    //trip
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    //plannedTripDetail
    public function plannedTripDetail()
    {
        return $this->hasMany(PlannedTripDetail::class);
    }

    //driver
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    //bus
    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }

    //route
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id')->withTrashed();
    }

    //reservations
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
