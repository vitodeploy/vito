<?php

namespace App\Actions\EnvironmentDeployment;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\EnvironmentDeploymentResource;
use App\Models\EnvironmentDeployment;
use Illuminate\Support\Facades\DB;

class BroadcastEnvironmentDeploymentUpdate
{
    public function broadcast(EnvironmentDeployment $run): void
    {
        DB::afterCommit(static function () use ($run): void {
            $run->refresh()->load('environment.application', 'deployments.component.server', 'triggeredBy');
            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $run->environment->application->project_id,
                type: 'environment-deployment.updated',
                data: new EnvironmentDeploymentResource($run),
            ));
        });
    }
}
