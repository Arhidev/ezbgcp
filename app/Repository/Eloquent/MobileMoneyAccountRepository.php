<?php

namespace App\Repository\Eloquent;

use App\Models\MobileMoneyAccount;
use App\Repository\MobileMoneyAccountRepositoryInterface;


/**
 * Class MobileMoneyAccountRepository.
 */
class MobileMoneyAccountRepository extends BaseRepository implements MobileMoneyAccountRepositoryInterface
{
    /**
     * @var MobileMoneyAccount
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param MobileMoneyAccount $model
     */
    public function __construct(MobileMoneyAccount $model)
    {
        $this->model = $model;
    }
}

