<?php

namespace App\NotificationChannels;

use App\Models\NotificationChannel;
use App\NotificationChannels\NotificationChannel as NotificationChannelInterface;

abstract class AbstractNotificationChannel implements NotificationChannelInterface
{
    public function __construct(protected NotificationChannel $notificationChannel) {}
}
