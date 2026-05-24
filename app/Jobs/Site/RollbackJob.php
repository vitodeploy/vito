<?php

namespace App\Jobs\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\DeploymentStatus;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\DeploymentResource;
use App\Models\Deployment;
use App\Notifications\DeploymentCompleted;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RollbackJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    protected ?Deployment $current = null;

    public function __construct(
        protected Deployment $deployment,
    ) {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $site = $this->deployment->site;
        $this->current = $site->deployments()->where('active', 1)->whereNotNull('release')->first();

        $this->run("site-{$site->id}", function () use ($site) {
            $this->deployment->site->server->ssh($site->user)->exec(
                view('ssh.modern-deployment.release', [
                    'site' => $site,
                    'releasePath' => $this->deployment->path(),
                ]),
                'release',
                $site->id
            );
            $this->deployment->activate();
            $this->deployment->status = DeploymentStatus::FINISHED;
            $this->deployment->save();
            $this->broadcastDeploymentUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $site = $this->deployment->site;

        $this->deployment->status = DeploymentStatus::FAILED;
        $this->deployment->save();
        $this->deployment->log?->write("Rollback failed: {$e->getMessage()}");
        $this->broadcastDeploymentUpdate();
        Notifier::send($site, new DeploymentCompleted($this->deployment, $site));

        if ($this->current) {
            $this->deployment->site->server->ssh($site->user)->exec(
                view('ssh.modern-deployment.release', [
                    'site' => $site,
                    'releasePath' => $this->current->path(),
                ]),
                'release',
                $site->id
            );
            $this->current->activate();
        }
    }

    private function broadcastDeploymentUpdate(): void
    {
        $this->deployment->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->deployment->site->server->project_id,
            type: 'deployment.updated',
            data: new DeploymentResource($this->deployment),
        ));
    }
}
