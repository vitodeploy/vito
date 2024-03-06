<?php

namespace App\ServerTypes;

use App\Contracts\ServerType;
use App\Models\Server;
use Closure;

abstract class AbstractType implements ServerType
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    protected function progress(int $percentage, ?string $step = null): Closure
    {
        return function () use ($percentage, $step) {
            $this->server->progress = $percentage;
            $this->server->progress_step = $step;
            $this->server->save();
        };
    }
}
