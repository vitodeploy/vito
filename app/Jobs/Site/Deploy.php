<?php

namespace App\Jobs\Site;

use App\Enums\DeploymentStatus;
use App\Jobs\Job;
use App\Models\Deployment;
use App\SSHCommands\System\RunScriptCommand;
use Throwable;

class Deploy extends Job
{
    protected Deployment $deployment;

    protected string $path;

    protected string $script;

    public function __construct(Deployment $deployment, string $path)
    {
        $this->script = $deployment->deploymentScript->content;
        $this->deployment = $deployment;
        $this->path = $path;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $ssh = $this->deployment->site->server->ssh();
        try {
            $ssh->exec(
                new RunScriptCommand($this->path, $this->script),
                'deploy',
                $this->deployment->site_id
            );
            $this->deployment->status = DeploymentStatus::FINISHED;
            $this->deployment->log_id = $ssh->log->id;
            $this->deployment->save();
        } catch (Throwable) {
            $this->deployment->log_id = $ssh->log->id;
            $this->deployment->save();
            $this->failed();
        }
    }

    public function failed(): void
    {
        $this->deployment->status = DeploymentStatus::FAILED;
        $this->deployment->save();
    }
}
