<?php

namespace App\Repository\Eloquent;

use App\Models\TripDetail;
use App\Repository\TripDetailRepositoryInterface;

class TripDetailRepository extends BaseRepository implements TripDetailRepositoryInterface
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
    public function __construct(TripDetail $model)
    {
        $this->model = $model;
    }
}
