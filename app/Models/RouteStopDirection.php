<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteStopDirection extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    use HasFactory;
    
}
