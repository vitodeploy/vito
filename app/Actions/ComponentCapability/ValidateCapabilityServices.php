<?php

namespace App\Actions\ComponentCapability;

use App\Enums\ServiceStatus;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Server;
use Illuminate\Validation\ValidationException;

class ValidateCapabilityServices
{
    /**
     * @throws ValidationException
     */
    public function validate(ComponentCapability $capability, Component $component, Server $server): void
    {
        if (
            $capability->component_id !== $component->id
            || $component->server_id !== $server->id
            || $component->environment->application->project_id !== $server->project_id
        ) {
            throw ValidationException::withMessages([
                'capabilities' => __('The capability must belong to the selected component and server in the same project.'),
            ]);
        }

        if (! is_array($capability->getAttribute('config') ?? [])) {
            throw ValidationException::withMessages([
                'config' => __('The capability configuration must be an array.'),
            ]);
        }

        $preset = $component->environment->application->preset();

        foreach ($capability->handler()->requiredServices($preset) as $type) {
            if (! $server->services()->where('type', $type)->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED, ServiceStatus::DISABLED])->exists()) {
                throw ValidationException::withMessages([
                    'capabilities' => __('The :service service must be installed on the server.', ['service' => $type]),
                ]);
            }
        }
    }
}
