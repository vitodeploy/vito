<?php

namespace App\Actions\Site;

use App\Enums\DeploymentStatus;
use App\Jobs\Site\RollbackJob;
use App\Models\Deployment;
use Illuminate\Validation\ValidationException;

class Rollback
{
    public function run(Deployment $deployment): void
    {
        $site = $deployment->site;
        /** ?Deployment $current */
        $current = $site->deployments()->where('active', 1)->whereNotNull('release')->first();

        if ($deployment->active) {
            throw ValidationException::withMessages([
                'deployment' => 'This release is already the active release!',
            ]);
        }

        if (! $deployment->release) {
            throw ValidationException::withMessages([
                'deployment' => 'Release not found!',
            ]);
        }

        $deployment->status = DeploymentStatus::DEPLOYING;
        $deployment->save();

        dispatch(new RollbackJob($deployment))->onQueue('ssh');
    }
}
