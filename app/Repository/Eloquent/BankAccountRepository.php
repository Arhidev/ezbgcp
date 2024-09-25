<?php

namespace App\Repository\Eloquent;

use App\Models\BankAccount;
use App\Repository\BankAccountRepositoryInterface;


/**
 * Class BankAccountRepository.
 */
class BankAccountRepository extends BaseRepository implements BankAccountRepositoryInterface
{
    /**
     * @var BankAccount
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param BankAccount $model
     */
    public function __construct(BankAccount $model)
    {
        $this->model = $model;
    }
}

