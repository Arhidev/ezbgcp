<?php

namespace App\Repository\Eloquent;

use App\Models\PlannedTrip;
use App\Repository\PlannedTripRepositoryInterface;


/**
 * Class PlannedTripRepository.
 */
class PlannedTripRepository extends BaseRepository implements PlannedTripRepositoryInterface
{
    /**
     * @var PlannedTrip
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param PlannedTrip $model
     */
    public function __construct(PlannedTrip $model)
    {
        $this->model = $model;
    }
}

