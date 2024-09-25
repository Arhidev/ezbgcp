<?php

namespace App\Repository\Eloquent;

use App\Models\RouteStop;
use App\Repository\RouteStopRepositoryInterface;

class RouteStopRepository extends BaseRepository implements RouteStopRepositoryInterface
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
    public function __construct(RouteStop $model)
    {
        $this->model = $model;
    }
}
