<?php

namespace App\Actions\EnvironmentDeployment;

use App\Actions\GitHook\TriggerRepositoryAutoDeploy;
use App\Enums\EnvironmentDeploymentStatus;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TriggerEnvironmentDeployFromWebhook
{
    public function run(int $environmentId, int $sourceControlId, string $repository, string $branch): void
    {
        $environment = Environment::query()->find($environmentId);
        if (! $environment) {
            return;
        }
        DB::transaction(function () use ($environment, $sourceControlId, $repository, $branch): void {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = app(TriggerRepositoryAutoDeploy::class)->matching($sourceControlId, $repository, $branch)->lockForUpdate()->find($environment->id);
            if ($environment) {
                app(DeployEnvironment::class)->run($environment);
            }
        });
    }

    public function failed(int $environmentId): void
    {
        $environment = Environment::query()->find($environmentId);
        if (! $environment) {
            return;
        }
        $run = new EnvironmentDeployment;
        $run->environment_id = $environment->id;
        $run->status = EnvironmentDeploymentStatus::FAILED;
        $run->release = (string) Str::ulid();
        $run->branch = $environment->branch;
        $run->finished_at = now();
        $run->last_error = 'Automatic deployment could not start. Check repository access, environment settings and component readiness.';
        $run->save();
        Log::error('Environment webhook deployment failed', ['environment_id' => $environment->id, 'run_id' => $run->id]);
        app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
    }
}
