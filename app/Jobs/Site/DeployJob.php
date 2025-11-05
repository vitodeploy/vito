<?php

namespace App\Jobs\Site;

use App\Enums\DeploymentStatus;
use App\Facades\Notifier;
use App\Models\Deployment;
use App\Models\ServerLog;
use App\Notifications\DeploymentCompleted;
use App\Services\ProcessManager\ProcessManager;
use App\SSH\OS\Git;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeployJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Deployment $deployment,
        protected bool $isModern = true
    ) {}

    public function handle(): void
    {
        $site = $this->deployment->site;
        $log = ServerLog::find($this->deployment->log_id);

        $this->run("server-{$site->server_id}", function () use ($site, $log) {
            if ($this->isModern) {
                $this->handleModernDeployment($site, $log);
            } else {
                $this->handleClassicDeployment($site, $log);
            }

            $this->deployment->status = DeploymentStatus::FINISHED;
            $this->deployment->save();
            $this->deployment->activate();
            Notifier::send($site, new DeploymentCompleted($this->deployment, $site));
        });
    }

    public function failed(Exception $e): void
    {
        $site = $this->deployment->site;
        $current = $site->deployments()->where('active', 1)->whereNotNull('release')->first();

        $this->deployment->status = DeploymentStatus::FAILED;
        $this->deployment->save();
        $this->deployment->activate();
        $this->deployment->log?->write("Deployment failed: {$e->getMessage()}");
        Notifier::send($site, new DeploymentCompleted($this->deployment, $site));

        if ($this->isModern && $current) {
            $this->deployment->site->server->ssh($site->user)->exec(
                view('ssh.modern-deployment.release', [
                    'site' => $site,
                    'releasePath' => $current->path(),
                ]),
                'release',
                $site->id
            );
            $current->activate();
        }
    }

    private function handleClassicDeployment($site, $log): void
    {
        $site->server->os()->runScript(
            path: $site->path,
            script: $site->deploymentScript->content,
            serverLog: $log,
            user: $site->user,
            variables: $site->environmentVariables($this->deployment),
            aliases: $site->environmentAliases(),
        );

        if ($site->deploymentScript->shouldRestartWorkers()) {
            /** @var ProcessManager $processManager */
            $processManager = $site->server->processManager()->handler();
            $workerIds = $site->workers()->pluck('id')->toArray();
            $processManager->restartByIds($workerIds, $site->id);
        }
    }

    private function handleModernDeployment($site, $log): void
    {
        app(Git::class)->clone($site, $this->deployment->path());

        // build
        $site->server->os()->runScript(
            path: $this->deployment->path(),
            script: $site->buildScript->content ?? '',
            serverLog: $log,
            user: $site->user,
            variables: $site->environmentVariables($this->deployment),
            aliases: $site->environmentAliases(),
        );

        // link resources
        $site->server->ssh($site->user)->exec(
            view('ssh.modern-deployment.link-resources', [
                'site' => $site,
                'releasePath' => $this->deployment->path(),
            ]),
            'link-resources',
            $site->id
        );

        // pre-flight
        $site->server->os()->runScript(
            path: $this->deployment->path(),
            script: $site->preFlightScript->content ?? '',
            serverLog: $log,
            user: $site->user,
            variables: $site->environmentVariables($this->deployment),
            aliases: $site->environmentAliases(),
        );

        // release
        $site->server->ssh($site->user)->exec(
            view('ssh.modern-deployment.release', [
                'site' => $site,
                'releasePath' => $this->deployment->path(),
            ]),
            'release',
            $site->id
        );

        if ($site->preFlightScript?->shouldRestartWorkers()) {
            /** @var ProcessManager $processManager */
            $processManager = $site->server->processManager()->handler();
            $workerIds = $site->workers()->pluck('id')->toArray();
            $processManager->restartByIds($workerIds, $site->id);
        }
    }
}
