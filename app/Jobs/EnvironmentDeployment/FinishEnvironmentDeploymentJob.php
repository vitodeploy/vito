<?php

namespace App\Jobs\EnvironmentDeployment;

use App\Actions\EnvironmentDeployment\FinishEnvironmentDeployment;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinishEnvironmentDeploymentJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 1200;

    public bool $failOnTimeout = true;

    protected function lockSeconds(): int
    {
        return $this->timeout + 60;
    }

    public function __construct(public int $runId)
    {
        $this->onConnection('ssh')->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("environment-deployment-finish-{$this->runId}", fn () => app(FinishEnvironmentDeployment::class)->finish($this->runId));
    }

    public function failed(Exception $e): void
    {
        app(FinishEnvironmentDeployment::class)->failed($this->runId);
    }
}
