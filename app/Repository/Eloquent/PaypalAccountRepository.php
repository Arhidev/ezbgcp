<?php

namespace App\Repository\Eloquent;

use App\Models\PaypalAccount;
use App\Repository\PaypalAccountRepositoryInterface;


/**
 * Class PaypalAccountRepository.
 */
class PaypalAccountRepository extends BaseRepository implements PaypalAccountRepositoryInterface
{
    /**
     * @var PaypalAccount
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param PaypalAccount $model
     */
    public function __construct(PaypalAccount $model)
    {
        $this->model = $model;
    }
}

