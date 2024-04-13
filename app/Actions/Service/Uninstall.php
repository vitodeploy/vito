<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

class Uninstall
{
    public function uninstall(Service $service): void
    {
        Validator::make([
            'service' => $service->id,
        ], $service->handler()->deletionRules())->validate();

        $service->status = ServiceStatus::UNINSTALLING;
        $service->save();

        dispatch(function () use ($service) {
            $service->handler()->uninstall();
            $service->delete();
        })->catch(function () use ($service) {
            $service->status = ServiceStatus::FAILED;
            $service->save();
        })->onConnection('ssh');
    }
}
