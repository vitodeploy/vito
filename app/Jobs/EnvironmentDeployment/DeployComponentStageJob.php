<?php

namespace App\Jobs\EnvironmentDeployment;

use App\Actions\EnvironmentDeployment\AdvanceEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\RunComponentStage;
use App\Enums\DeploymentStage;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeployComponentStageJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 1200;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $deploymentId,
        public int $componentId,
        public int $runId,
        public DeploymentStage $stage,
        public bool $recovering = false,
    ) {
        $this->onConnection('ssh')->onQueue('ssh');
    }

    protected function lockSeconds(): int
    {
        return $this->timeout + 60;
    }

    public function handle(): void
    {
        $completed = false;
        $this->run("component-{$this->componentId}", function () use (&$completed): void {
            app(RunComponentStage::class)->run($this->deploymentId, $this->stage, $this->recovering);
            $completed = true;
        });
        if ($completed) {
            app(AdvanceEnvironmentDeployment::class)->advance($this->runId, $this->stage, $this->recovering);
        }
    }

    public function failed(Exception $e): void
    {
        app(RunComponentStage::class)->failed($this->deploymentId, $this->stage, $this->recovering);
        AdvanceEnvironmentDeploymentJob::dispatch($this->runId, $this->componentId, $this->stage, $this->recovering);
    }
}
