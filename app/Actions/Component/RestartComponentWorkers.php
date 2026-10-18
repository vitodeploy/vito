<?php

namespace App\Actions\Component;

use App\Actions\Worker\RestartWorkers;
use App\Models\Component;

class RestartComponentWorkers
{
    public function restart(Component $component): void
    {
        if (! app(HasComponentRelease::class)->check($component)) {
            return;
        }

        app(RestartWorkers::class)->restart($component->server, $component->workers()->with('component.environment.application', 'component.isolatedUser')->get());
    }
}
