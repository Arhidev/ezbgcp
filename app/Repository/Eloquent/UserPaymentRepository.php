<?php

namespace App\Repository\Eloquent;

use App\Models\UserPayment;
use App\Repository\UserPaymentRepositoryInterface;


/**
 * Class UserPaymentRepository.
 */
class UserPaymentRepository extends BaseRepository implements UserPaymentRepositoryInterface
{
    /**
     * @var UserPayment
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param UserPayment $model
     */
    public function __construct(UserPayment $model)
    {
        $this->model = $model;
    }
}

