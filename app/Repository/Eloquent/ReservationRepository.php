<?php

namespace App\Repository\Eloquent;

use App\Models\Reservation;
use App\Repository\ReservationRepositoryInterface;

class ReservationRepository extends BaseRepository implements ReservationRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Reservation $model)
    {
        $this->model = $model;
    }
}
