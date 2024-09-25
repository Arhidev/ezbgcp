<?php

namespace App\Repository\Eloquent;

use App\Models\UserRefund;
use App\Repository\UserRefundRepositoryInterface;


/**
 * Class UserRefundRepository.
 */
class UserRefundRepository extends BaseRepository implements UserRefundRepositoryInterface
{
    /**
     * @var UserRefund
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param UserRefund $model
     */
    public function __construct(UserRefund $model)
    {
        $this->model = $model;
    }
}

