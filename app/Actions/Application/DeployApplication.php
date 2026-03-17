<?php

namespace App\Actions\Application;

use App\DTOs\SocketEventDTO;
use App\Enums\DeploymentStatus;
use App\Events\SocketEvent;
use App\Http\Resources\DeploymentResource;
use App\Jobs\Application\DeployJob;
use App\Models\Application;
use App\Models\Deployment;
use App\Models\ServerLog;

class DeployApplication
{
    public function deploy(Application $application): Deployment
    {
        $log = ServerLog::log(
            $application->server,
            'application-deployment',
            'Deploying application '.$application->domain.'...',
            $application,
        );

        $deployment = new Deployment([
            'application_id' => $application->id,
            'log_id' => $log->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]);
        $deployment->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $application->server->project_id,
            type: 'deployment.created',
            data: new DeploymentResource($deployment),
        ));

        dispatch(new DeployJob($application, $deployment))->onQueue('ssh');

        return $deployment;
    }
}
