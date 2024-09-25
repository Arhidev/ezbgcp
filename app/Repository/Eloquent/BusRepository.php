<?php

namespace App\Repository\Eloquent;

use App\Models\Bus;
use App\Repository\BusRepositoryInterface;

class BusRepository extends BaseRepository implements BusRepositoryInterface
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
    public function __construct(Bus $model)
    {
        $this->model = $model;
    }
}
