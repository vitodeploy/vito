<?php

namespace App\Listeners;

use App\Events\Broadcast;
use Illuminate\Support\Facades\Cache;

class BroadcastListener
{
    public function __construct()
    {
    }

    public function handle(Broadcast $event): void
    {
        Cache::set('broadcast', [
            'type' => $event->type,
            'data' => $event->data
        ], now()->addMinutes(5));
    }
}
