<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverInformation extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function documents()
    {
        return $this->hasMany(DriverDocument::class, 'driver_information_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
