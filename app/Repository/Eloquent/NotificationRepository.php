<?php

namespace App\Repository\Eloquent;

use App\Models\Notification;
use App\Repository\NotificationRepositoryInterface;


/**
 * Class NotificationRepository.
 */
class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    /**
     * @var Notification
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Notification $model
     */
    public function __construct(Notification $model)
    {
        $this->model = $model;
    }
}

