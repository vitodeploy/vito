<?php

namespace App\Jobs\Application;

use App\DTOs\SocketEventDTO;
use App\Enums\ApplicationStatus;
use App\Enums\DeploymentStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\DeploymentResource;
use App\Models\Application;
use App\Models\Deployment;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Application $application) {}

    public function handle(): void
    {
        $this->run("server-{$this->application->server_id}", function () {
            $log = ServerLog::log(
                $this->application->server,
                'application-installation',
                'Installing application '.$this->application->domain.'...',
                $this->application,
            );

            $deployment = new Deployment([
                'application_id' => $this->application->id,
                'log_id' => $log->id,
                'status' => DeploymentStatus::DEPLOYING,
            ]);
            $deployment->save();

            $this->application->type()->install();

            $deployActionClass = config('application.types.'.$this->application->type.'.deploy_action');
            if ($deployActionClass && class_exists($deployActionClass)) {
                $action = new $deployActionClass;
                $action->deploy($this->application, $deployment);
            }

            $log->write('Application installed successfully.');

            $deployment->status = DeploymentStatus::FINISHED;
            $deployment->save();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->application->server->project_id,
                type: 'deployment.updated',
                data: new DeploymentResource($deployment->refresh()),
            ));

            $this->application->update([
                'status' => ApplicationStatus::READY,
            ]);
            $this->broadcastUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->application->status = ApplicationStatus::INSTALLATION_FAILED;
        $this->application->save();
        $this->broadcastUpdate();
        ServerLog::log(
            $this->application->server,
            'application-installation-failed',
            $e->getMessage(),
            $this->application,
        );
    }

    private function broadcastUpdate(): void
    {
        $this->application->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->application->server->project_id,
            type: 'application.updated',
            data: new ApplicationResource($this->application),
        ));
    }
}
