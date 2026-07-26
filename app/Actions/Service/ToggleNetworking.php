<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Jobs\Service\ToggleNetworkingJob;
use App\Models\Service;
use App\Services\Redis\Redis;
use App\Services\SupportsNetworking;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ToggleNetworking
{
    /**
     * @throws ValidationException
     */
    public function enable(Service $service): void
    {
        $this->validate($service);

        if ($service->type === Redis::type() && ($service->secret === null || $service->secret === '')) {
            $service->secret = Str::random(32);
        }

        $this->dispatch($service, true);
    }

    /**
     * @throws ValidationException
     */
    public function disable(Service $service): void
    {
        $this->validate($service);

        $this->dispatch($service, false);
    }

    private function dispatch(Service $service, bool $enable): void
    {
        $previousStatus = $service->status;
        $service->status = ServiceStatus::RESTARTING;
        $service->save();

        dispatch(new ToggleNetworkingJob($service, $enable, $previousStatus))->onQueue('ssh');
    }

    /**
     * @throws ValidationException
     */
    private function validate(Service $service): void
    {
        if (! $service->hasHandler() || ! $service->handler() instanceof SupportsNetworking) {
            throw ValidationException::withMessages([
                'service' => __('This service does not support networking.'),
            ]);
        }

        if (! in_array($service->status, SyncServiceStatus::SETTLED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'service' => __('Wait for the service to settle before changing its networking.'),
            ]);
        }
    }
}
