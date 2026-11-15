<?php

namespace App\Console\Commands;

use App\Actions\EnvironmentDeployment\FailStalledEnvironmentDeployments;
use Illuminate\Console\Command;

class CheckStalledEnvironmentDeploymentsCommand extends Command
{
    protected $signature = 'deployments:check-stalled';

    protected $description = 'Fail overdue environment deployment stages and resume queued deployments';

    public function handle(): void
    {
        app(FailStalledEnvironmentDeployments::class)->handle();
    }
}
