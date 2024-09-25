<?php

namespace App\Repository\Eloquent;

use App\Models\Setting;
use App\Repository\SettingRepositoryInterface;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
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
    public function __construct(Setting $model)
    {
        $this->model = $model;
    }
}
