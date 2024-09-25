<?php

namespace App\Repository\Eloquent;

use App\Models\RouteStopDirection;
use App\Repository\RouteStopDirectionRepositoryInterface;

class RouteStopDirectionRepository extends BaseRepository implements RouteStopDirectionRepositoryInterface
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
    public function __construct(RouteStopDirection $model)
    {
        $this->model = $model;
    }
}
