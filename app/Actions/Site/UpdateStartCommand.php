<?php

namespace App\Actions\Site;

use App\Actions\Worker\ManageWorker;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Services\ProcessManager\ProcessManager;
use App\SiteTypes\AbstractProxiedSiteType;
use Illuminate\Support\Facades\Validator;

class UpdateStartCommand
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): WorkerStartCommandUpdateResult
    {
        $validated = Validator::make($input, [
            'start_command' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'restart' => ['sometimes', 'boolean'],
        ])->validate();

        $site->jsonUpdate('type_data', 'start_command', $validated['start_command']);

        $type = $site->type();
        $worker = $type instanceof AbstractProxiedSiteType ? $type->bootstrapWorker() : null;

        if ($worker === null) {
            return WorkerStartCommandUpdateResult::PreFirstDeploy;
        }

        $worker->command = $validated['start_command'];
        $worker->save();

        /** @var ProcessManager $processManager */
        $processManager = $site->server->processManager()->handler();
        $processManager->writeConfig($worker);

        if ($validated['restart'] ?? false) {
            app(ManageWorker::class)->restart($worker);

            return WorkerStartCommandUpdateResult::Restarting;
        }

        return WorkerStartCommandUpdateResult::PendingRestart;
    }
}

enum WorkerStartCommandUpdateResult
{
    case PreFirstDeploy;
    case PendingRestart;
    case Restarting;
}
