<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\RuntimePresets\AbstractRuntimePreset;
use Illuminate\Validation\ValidationException;

class GetApplicationRuntime
{
    public function get(Application $application, string $field = 'runtime'): AbstractRuntimePreset
    {
        return $application->presetOrNull() ?? throw ValidationException::withMessages([
            $field => 'The runtime plugin is missing. Restore it before changing runtime configuration or deploying.',
        ]);
    }
}
