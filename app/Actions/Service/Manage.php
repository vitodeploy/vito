<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Jobs\Service\ManageJob;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

class Manage
{
    public function start(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::STARTING;
        $service->save();
        dispatch(new ManageJob($service, 'start', ServiceStatus::READY))->onQueue('ssh');
    }

    public function stop(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::STOPPING;
        $service->save();
        dispatch(new ManageJob($service, 'stop', ServiceStatus::STOPPED))->onQueue('ssh');
    }

    public function restart(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::RESTARTING;
        $service->save();
        dispatch(new ManageJob($service, 'restart', ServiceStatus::READY))->onQueue('ssh');
    }

    public function reload(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::RELOADING;
        $service->save();
        dispatch(new ManageJob($service, 'reload', ServiceStatus::READY))->onQueue('ssh');
    }

    public function enable(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::ENABLING;
        $service->save();
        dispatch(new ManageJob($service, 'enable', ServiceStatus::READY))->onQueue('ssh');
    }

    public function disable(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::DISABLING;
        $service->save();
        dispatch(new ManageJob($service, 'disable', ServiceStatus::DISABLED))->onQueue('ssh');
    }

    private function validate(Service $service): void
    {
        if (! $service->handler()->canBeManaged()) {
            throw ValidationException::withMessages([
                'service' => __('This service cannot be managed.'),
            ]);
        }
    }
}
