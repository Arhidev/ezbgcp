<?php

namespace App\Repository\Eloquent;

use App\Models\TripSearchResult;
use App\Repository\TripSearchResultRepositoryInterface;


/**
 * Class TripSearchResultRepository.
 */
class TripSearchResultRepository extends BaseRepository implements TripSearchResultRepositoryInterface
{
    /**
     * @var TripSearchResult
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param TripSearchResult $model
     */
    public function __construct(TripSearchResult $model)
    {
        $this->model = $model;
    }
}

