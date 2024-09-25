<?php

namespace App\Repository\Eloquent;

use App\Models\DriverDocument;
use App\Repository\DriverDocumentRepositoryInterface;


/**
 * Class DriverDocumentRepository.
 */
class DriverDocumentRepository extends BaseRepository implements DriverDocumentRepositoryInterface
{
    /**
     * @var DriverDocument
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param DriverDocument $model
     */
    public function __construct(DriverDocument $model)
    {
        $this->model = $model;
    }
}

