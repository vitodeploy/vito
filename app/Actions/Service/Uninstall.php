<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Jobs\Service\UninstallJob;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Uninstall
{
    /*
     * @TODO: Implement the uninstaller for all service handlers
     */
    public function uninstall(Service $service): void
    {
        $previousStatus = DB::transaction(function () use ($service): ServiceStatus {
            Server::query()->lockForUpdate()->findOrFail($service->server_id);
            $service->refresh();
            $this->validate($service);
            $previousStatus = $service->status;
            $service->status = ServiceStatus::UNINSTALLING;
            $service->save();

            return $previousStatus;
        });

        dispatch(new UninstallJob($service, $previousStatus))->onQueue('ssh')->afterCommit();
    }

    public function validate(Service $service): void
    {
        Validator::make([
            'service' => $service->id,
        ], $service->handler()->deletionRules())->validate();
    }
}
