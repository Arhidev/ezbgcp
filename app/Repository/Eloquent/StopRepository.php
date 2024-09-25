<?php

namespace App\Repository\Eloquent;

use App\Models\Stop;
use App\Repository\StopRepositoryInterface;

class StopRepository extends BaseRepository implements StopRepositoryInterface
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
    public function __construct(Stop $model)
    {
        $this->model = $model;
    }
}
