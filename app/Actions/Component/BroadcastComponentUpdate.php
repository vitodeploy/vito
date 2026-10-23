<?php

namespace App\Actions\Component;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\ComponentResource;
use App\Models\Component;
use App\Models\Environment;

class BroadcastComponentUpdate
{
    public function deleted(Component $component): void
    {
        $environment = Environment::query()->with('application')->find($component->environment_id);
        if (! $environment?->application) {
            return;
        }

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $environment->application->project_id,
            type: 'component.deleted',
            data: [
                'id' => $component->id, 'environment_id' => $component->environment_id,
                'application_status' => $environment->application->status->getText(),
                'environment_status' => $environment->status->getText(),
            ],
        ));
    }

    public function broadcast(Component $component): void
    {
        $component->refresh()->load(['environment.application', 'server', 'capabilities', 'backends.targetComponent']);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $component->environment->application->project_id,
            type: 'component.updated',
            data: new ComponentResource($component),
        ));
    }
}
