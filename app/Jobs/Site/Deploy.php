<?php

namespace App\Jobs\Site;

use App\Enums\DeploymentStatus;
use App\Events\Broadcast;
use App\Helpers\SSH;
use App\Jobs\Job;
use App\Models\Deployment;
use App\SSHCommands\System\RunScript;
use Throwable;

class Deploy extends Job
{
    protected Deployment $deployment;

    protected string $path;

    protected string $script;

    protected SSH $ssh;

    public function __construct(Deployment $deployment, string $path, string $script)
    {
        $this->deployment = $deployment;
        $this->path = $path;
        $this->script = $script;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->ssh = $this->deployment->site->server->ssh();
        $this->ssh->exec(
            new RunScript($this->path, $this->script),
            'deploy',
            $this->deployment->site_id
        );
        $this->deployment->status = DeploymentStatus::FINISHED;
        $this->deployment->log_id = $this->ssh->log->id;
        $this->deployment->save();
        event(
            new Broadcast('deploy-site-finished', [
                'deployment' => $this->deployment,
            ])
        );
    }

    public function failed(): void
    {
        $this->deployment->status = DeploymentStatus::FAILED;
        if ($this->ssh->log) {
            $this->deployment->log_id = $this->ssh->log->id;
        }
        $this->deployment->save();
        event(
            new Broadcast('deploy-site-failed', [
                'deployment' => $this->deployment,
            ])
        );
    }
}
