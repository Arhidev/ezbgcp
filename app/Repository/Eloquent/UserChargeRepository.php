<?php

namespace App\Repository\Eloquent;

use App\Models\UserCharge;
use App\Repository\UserChargeRepositoryInterface;


/**
 * Class UserChargeRepository.
 */
class UserChargeRepository extends BaseRepository implements UserChargeRepositoryInterface
{
    /**
     * @var UserCharge
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param UserCharge $model
     */
    public function __construct(UserCharge $model)
    {
        $this->model = $model;
    }
}

