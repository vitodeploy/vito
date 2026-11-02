<?php

namespace App\Jobs\EnvironmentDeployment;

use App\Actions\EnvironmentDeployment\AdvanceEnvironmentDeployment;
use App\Enums\DeploymentStage;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AdvanceEnvironmentDeploymentJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(public int $runId, public int $componentId, public DeploymentStage $stage, public bool $recovering) {}

    public function handle(): void
    {
        $released = false;
        $this->run("component-{$this->componentId}", function () use (&$released): void {
            $released = true;
        });
        if ($released) {
            app(AdvanceEnvironmentDeployment::class)->advance($this->runId, $this->stage, $this->recovering);
        }
    }

    public function failed(Exception $e): void
    {
        app(AdvanceEnvironmentDeployment::class)->failed($this->runId);
    }
}
