<?php

namespace App\Actions\EnvironmentVariable;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Enums\ApplicationStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteEnvironmentVariable
{
    public function delete(Environment $environment, EnvironmentVariable $variable): void
    {
        DB::transaction(function () use ($environment, $variable): void {
            $application = Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);

            if ($application->status !== ApplicationStatus::READY || $environment->status !== EnvironmentStatus::READY) {
                throw ValidationException::withMessages(['key' => 'Variables cannot be changed during teardown.']);
            }

            $environment->variables()->findOrFail($variable->id)->delete();
            app(MarkEnvironmentForRedeploy::class)->mark($environment);
        });
    }
}
