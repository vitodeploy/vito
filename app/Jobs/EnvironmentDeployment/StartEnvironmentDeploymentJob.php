<?php

namespace App\Jobs\EnvironmentDeployment;

use App\Actions\EnvironmentDeployment\StartEnvironmentDeployment;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StartEnvironmentDeploymentJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $this->run("environment-deployment-start-{$this->runId}", fn () => app(StartEnvironmentDeployment::class)->start($this->runId));
    }

    public function failed(Exception $e): void
    {
        app(StartEnvironmentDeployment::class)->failed($this->runId);
    }
}
