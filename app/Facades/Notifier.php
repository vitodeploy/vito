<?php

namespace App\Facades;

use App\Contracts\Notification;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void send(object $notifiable, Notification $notification)
 */
class Notifier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notifier';
    }
}
