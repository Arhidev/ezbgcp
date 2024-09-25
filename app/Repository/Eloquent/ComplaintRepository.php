<?php

namespace App\Repository\Eloquent;

use App\Models\Complaint;
use App\Repository\ComplaintRepositoryInterface;


/**
 * Class ComplaintRepository.
 */
class ComplaintRepository extends BaseRepository implements ComplaintRepositoryInterface
{
    /**
     * @var Complaint
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Complaint $model
     */
    public function __construct(Complaint $model)
    {
        $this->model = $model;
    }
}

