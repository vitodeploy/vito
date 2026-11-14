<?php

namespace App\Actions\Component;

use App\Models\Component;

class HasComponentRelease
{
    public function check(Component $component): bool
    {
        return trim($component->server->ssh($component->user)->exec(
            view('ssh.components.has-current', ['component' => $component]),
        )) === 'VITO_COMPONENT_CURRENT_EXISTS';
    }
}
