<?php

namespace App\Jobs\Application;

use App\DTOs\SocketEventDTO;
use App\Enums\DeploymentStatus;
use App\Events\SocketEvent;
use App\Http\Resources\DeploymentResource;
use App\Models\Application;
use App\Models\Deployment;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeployJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Application $application,
        protected Deployment $deployment,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->application->server_id}", function () {
            $deployActionClass = config('application.types.'.$this->application->type.'.deploy_action');
            if ($deployActionClass && class_exists($deployActionClass)) {
                $action = new $deployActionClass;
                $action->deploy($this->application, $this->deployment);
            }

            $this->deployment->log?->write('Deployment completed successfully.');
            $this->deployment->status = DeploymentStatus::FINISHED;
            $this->deployment->save();
            $this->broadcastDeployment();
        });
    }

    public function failed(Exception $e): void
    {
        $this->deployment->log?->write('Deployment failed: '.$e->getMessage());
        $this->deployment->status = DeploymentStatus::FAILED;
        $this->deployment->save();
        $this->broadcastDeployment();
    }

    private function broadcastDeployment(): void
    {
        $this->deployment->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->application->server->project_id,
            type: 'deployment.updated',
            data: new DeploymentResource($this->deployment),
        ));
    }
}
