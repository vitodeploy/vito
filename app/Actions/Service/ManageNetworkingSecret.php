<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Jobs\Service\UpdateNetworkingSecretJob;
use App\Models\Service;
use App\Services\SupportsNetworkingSecret;
use Illuminate\Validation\ValidationException;

class ManageNetworkingSecret
{
    /**
     * @throws ValidationException
     */
    public function regenerate(Service $service): void
    {
        $this->validate($service);

        $this->dispatch($service, remove: false);
    }

    /**
     * @throws ValidationException
     */
    public function remove(Service $service): void
    {
        $handler = $this->validate($service);

        if ($handler->networkingEnabled() || ($service->type_data['networking_effective'] ?? null) === true) {
            throw ValidationException::withMessages([
                'service' => __('Disable networking before removing the password.'),
            ]);
        }

        $this->dispatch($service, remove: true);
    }

    private function dispatch(Service $service, bool $remove): void
    {
        $previousStatus = $service->status;

        $service->status = ServiceStatus::RESTARTING;
        $service->save();

        dispatch(new UpdateNetworkingSecretJob($service, $remove, $previousStatus))->onQueue('ssh');
    }

    /**
     * @throws ValidationException
     */
    private function validate(Service $service): SupportsNetworkingSecret
    {
        $handler = $service->hasHandler() ? $service->handler() : null;

        if (! $handler instanceof SupportsNetworkingSecret) {
            throw ValidationException::withMessages([
                'service' => __('This service does not use a networking password.'),
            ]);
        }

        if ($handler->networkingSecret() === null) {
            throw ValidationException::withMessages([
                'service' => __('This service does not have a password.'),
            ]);
        }

        if (! in_array($service->status, SyncServiceStatus::SETTLED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'service' => __('Wait for the service to settle before changing its password.'),
            ]);
        }

        return $handler;
    }
}
