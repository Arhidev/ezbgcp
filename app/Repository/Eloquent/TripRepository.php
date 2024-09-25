<?php

namespace App\Repository\Eloquent;

use App\Models\Trip;
use App\Repository\TripRepositoryInterface;

class TripRepository extends BaseRepository implements TripRepositoryInterface
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
    public function __construct(Trip $model)
    {
        $this->model = $model;
    }
}
