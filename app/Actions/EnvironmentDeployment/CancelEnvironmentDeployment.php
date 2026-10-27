<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\EnvironmentDeploymentStatus;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelEnvironmentDeployment
{
    public function cancel(EnvironmentDeployment $run): void
    {
        DB::transaction(function () use ($run): void {
            Application::query()->lockForUpdate()->findOrFail($run->environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($run->environment_id);
            $run = EnvironmentDeployment::query()->lockForUpdate()->findOrFail($run->id);
            if (! $run->canCancel()) {
                throw ValidationException::withMessages(['deployment' => 'Only pending deployments or running deployments before release can be cancelled.']);
            }
            app(AdvanceEnvironmentDeployment::class)->finish($run, EnvironmentDeploymentStatus::CANCELLED);
        });
    }
}
