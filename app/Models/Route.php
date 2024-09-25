<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Route extends Model
{
    use SoftDeletes;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function stops()
    {
        return $this->belongsToMany(Stop::class, 'route_stops');
    }
    public function routeStops()
    {
        return $this->hasMany(RouteStop::class);
    }

    //plannedTrips
    public function plannedTrips()
    {
        return $this->hasMany(PlannedTrip::class);
    }
}
