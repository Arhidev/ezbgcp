<?php

namespace App\Repository\Eloquent;

use App\Models\Place;
use App\Repository\PlaceRepositoryInterface;


/**
 * Class PlaceRepository.
 */
class PlaceRepository extends BaseRepository implements PlaceRepositoryInterface
{
    /**
     * @var Place
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Place $model
     */
    public function __construct(Place $model)
    {
        $this->model = $model;
    }
}

