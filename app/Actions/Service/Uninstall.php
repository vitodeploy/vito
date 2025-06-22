<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

class Uninstall
{
    /*
     * @TODO: Implement the uninstaller for all service handlers
     */
    public function uninstall(Service $service): void
    {
        Validator::make([
            'service' => $service->id,
        ], $service->handler()->deletionRules())->validate();

        $service->status = ServiceStatus::UNINSTALLING;
        $service->save();

        dispatch(function () use ($service): void {
            $service->handler()->uninstall();
            $service->delete();
        })->catch(function () use ($service): void {
            $service->status = ServiceStatus::FAILED;
            $service->save();
        })->onQueue('ssh-unique');
    }
}
