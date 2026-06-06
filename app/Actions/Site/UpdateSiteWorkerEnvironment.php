<?php

namespace App\Actions\Site;

use App\Actions\Worker\UpdateWorkerEnvironment;
use App\Actions\Worker\WorkerEnvironmentUpdateResult;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\SiteTypes\AbstractProxiedSiteType;
use Illuminate\Support\Facades\Validator;

class UpdateSiteWorkerEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): WorkerEnvironmentUpdateResult
    {
        $type = $site->type();
        $worker = $type instanceof AbstractProxiedSiteType ? $type->bootstrapWorker() : null;

        if ($worker !== null) {
            return app(UpdateWorkerEnvironment::class)->update($worker, $input);
        }

        $validated = Validator::make($input, UpdateWorkerEnvironment::rules())->validate();

        $site->worker_environment = UpdateWorkerEnvironment::processVariables(
            $validated['variables'],
            $site->worker_environment,
        );
        $site->save();

        return WorkerEnvironmentUpdateResult::PreFirstDeploy;
    }
}
