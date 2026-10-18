<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\EnvironmentDeploymentStatus;
use App\Models\Environment;
use Illuminate\Validation\ValidationException;

class AssertEnvironmentIdle
{
    public function validate(Environment $environment, string $field): void
    {
        if ($environment->deployments()->whereIn('status', [EnvironmentDeploymentStatus::PENDING, EnvironmentDeploymentStatus::RUNNING])->exists()) {
            throw ValidationException::withMessages([$field => 'Wait for the environment deployment to finish before making this change.']);
        }
    }
}
