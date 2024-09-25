<?php

namespace App\Repository\Eloquent;

use App\Models\DriverInformation;
use App\Repository\DriverInformationRepositoryInterface;


/**
 * Class DriverInformationRepository.
 */
class DriverInformationRepository extends BaseRepository implements DriverInformationRepositoryInterface
{
    /**
     * @var DriverInformation
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param DriverInformation $model
     */
    public function __construct(DriverInformation $model)
    {
        $this->model = $model;
    }
}

