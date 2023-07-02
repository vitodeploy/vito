<?php

namespace App\NotificationChannels;

use App\Contracts\NotificationChannel as NotificationChannelContract;
use App\Models\NotificationChannel;

abstract class AbstractProvider implements NotificationChannelContract
{
    protected NotificationChannel $notificationChannel;

    public function __construct(NotificationChannel $notificationChannel)
    {
        $this->notificationChannel = $notificationChannel;
    }
}
