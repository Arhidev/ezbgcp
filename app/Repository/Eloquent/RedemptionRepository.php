<?php

namespace App\Repository\Eloquent;

use App\Models\Redemption;
use App\Repository\RedemptionRepositoryInterface;


/**
 * Class RedemptionRepository.
 */
class RedemptionRepository extends BaseRepository implements RedemptionRepositoryInterface
{
    /**
     * @var Redemption
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Redemption $model
     */
    public function __construct(Redemption $model)
    {
        $this->model = $model;
    }
}

