<?php

namespace App\Repository\Eloquent;

use App\Models\Currency;
use App\Repository\CurrencyRepositoryInterface;

class CurrencyRepository extends BaseRepository implements CurrencyRepositoryInterface
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
    public function __construct(Currency $model)
    {
        $this->model = $model;
    }
}
