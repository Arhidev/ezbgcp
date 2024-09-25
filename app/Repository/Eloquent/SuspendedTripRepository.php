<?php

namespace App\Repository\Eloquent;

use App\Models\SuspendedTrip;
use App\Repository\SuspendedTripRepositoryInterface;

class SuspendedTripRepository extends BaseRepository implements SuspendedTripRepositoryInterface
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
    public function __construct(SuspendedTrip $model)
    {
        $this->model = $model;
    }
}
