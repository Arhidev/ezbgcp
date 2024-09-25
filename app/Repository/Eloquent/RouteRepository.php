<?php

namespace App\Repository\Eloquent;

use App\Models\Route;
use App\Repository\RouteRepositoryInterface;

class RouteRepository extends BaseRepository implements RouteRepositoryInterface
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
    public function __construct(Route $model)
    {
        $this->model = $model;
    }
}
