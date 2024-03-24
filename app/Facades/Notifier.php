<?php

namespace App\Facades;

use App\Notifications\NotificationInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void send(object $notifiable, NotificationInterface $notification)
 */
class Notifier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notifier';
    }
}
