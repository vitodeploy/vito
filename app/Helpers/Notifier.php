<?php

namespace App\Helpers;

use App\Models\NotificationChannel;
use App\Notifications\NotificationInterface;

class Notifier
{
    /**
     * In the future we can send notifications based on the notifiable instance,
     * For example, If it was a server then we will send the channels specified by that server
     * For now, we will send all channels.
     */
    public function send(object $notifiable, NotificationInterface $notification): void
    {
        NotificationChannel::notifyAll($notification);
    }
}
