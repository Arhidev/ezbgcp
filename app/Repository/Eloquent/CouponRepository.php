<?php

namespace App\Repository\Eloquent;

use App\Models\Coupon;
use App\Repository\CouponRepositoryInterface;


/**
 * Class CouponRepository.
 */
class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    /**
     * @var Coupon
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Coupon $model
     */
    public function __construct(Coupon $model)
    {
        $this->model = $model;
    }
}

