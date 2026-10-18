<?php

namespace App\Actions\Environment;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;

class MarkEnvironmentForRedeploy
{
    public function mark(Environment $environment): void
    {
        $environment->increment('variables_version');
        $environment->components()->update(['needs_redeploy' => true]);
        $event = new SocketEventDTO(
            projectId: $environment->application->project_id,
            type: 'environment.updated',
            data: ['id' => $environment->id, 'application_id' => $environment->application_id],
        );
        DB::afterCommit(static fn () => SocketEvent::dispatch($event));
    }
}
