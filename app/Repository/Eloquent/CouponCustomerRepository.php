<?php

namespace App\Repository\Eloquent;

use App\Models\CouponCustomer;
use App\Repository\CouponCustomerRepositoryInterface;


/**
 * Class CouponCustomerRepository.
 */
class CouponCustomerRepository extends BaseRepository implements CouponCustomerRepositoryInterface
{
    /**
     * @var CouponCustomer
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param CouponCustomer $model
     */
    public function __construct(CouponCustomer $model)
    {
        $this->model = $model;
    }
}

