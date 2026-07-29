<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Jobs\Service\ToggleNetworkingJob;
use App\Models\Service;
use App\Services\SupportsNetworking;
use Illuminate\Validation\ValidationException;

class ToggleNetworking
{
    /**
     * @throws ValidationException
     */
    public function enable(Service $service): void
    {
        $handler = $this->validate($service);

        $handler->prepareNetworking();
        $service->save();

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
        $service->refresh();

        $previousStatus = $service->status;

        if (! array_key_exists('networking', $service->type_data ?? [])) {
            $service->jsonUpdate('type_data', 'networking', false, save: false);
        }

        $service->status = ServiceStatus::RESTARTING;
        $service->save();

        dispatch(new ToggleNetworkingJob($service, $enable, $previousStatus))->onQueue('ssh');
    }

    /**
     * @throws ValidationException
     */
    private function validate(Service $service): SupportsNetworking
    {
        $handler = $service->hasHandler() ? $service->handler() : null;

        if (! $handler instanceof SupportsNetworking) {
            throw ValidationException::withMessages([
                'service' => __('This service does not support networking.'),
            ]);
        }

        if (! in_array($service->status, SyncServiceStatus::SETTLED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'service' => __('Wait for the service to settle before changing its networking.'),
            ]);
        }

        return $handler;
    }
}
