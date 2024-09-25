<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //reservation
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }


}
