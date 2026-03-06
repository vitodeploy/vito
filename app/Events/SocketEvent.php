<?php

namespace App\Events;

use App\DTOs\SocketEventDTO;
use Illuminate\Foundation\Events\Dispatchable;

class SocketEvent
{
    use Dispatchable;

    public function __construct(
        public readonly SocketEventDTO $data,
    ) {}
}
