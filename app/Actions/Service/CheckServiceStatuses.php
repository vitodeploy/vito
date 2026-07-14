<?php

namespace App\Actions\Service;

use App\DTOs\SocketEventDTO;
use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Events\SocketEvent;
use App\Exceptions\SSHError;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
use App\Models\Service;

class CheckServiceStatuses
{
    private const array SETTLED_STATUSES = [
        ServiceStatus::READY,
        ServiceStatus::STOPPED,
        ServiceStatus::FAILED,
        ServiceStatus::DISABLED,
    ];

    /**
     * @throws SSHError
     */
    public function check(Server $server): void
    {
        $services = $server->services()
            ->whereIn('status', self::SETTLED_STATUSES)
            ->get();

        $checkable = [];
        $units = [];
        foreach ($services as $service) {
            if (! $service->hasHandler()) {
                continue;
            }
            $handler = $service->handler();
            if (! $handler->shouldCheckStatus()) {
                continue;
            }
            $checkable[] = $service;
            $units[] = $handler->unit();
        }

        if ($units === []) {
            return;
        }

        $states = $server->systemd()->activeStates($units);

        if ($states === []) {
            return;
        }

        foreach ($checkable as $index => $service) {
            $this->syncStatus($server, $service, $states[$index] ?? '');
        }
    }

    private function syncStatus(Server $server, Service $service, string $state): void
    {
        $newStatus = match ($state) {
            'active' => ServiceStatus::READY,
            'inactive' => ServiceStatus::STOPPED,
            'failed' => ServiceStatus::FAILED,
            default => null,
        };

        $previousStatus = $service->status;

        if (! $newStatus instanceof ServiceStatus || $newStatus === $previousStatus) {
            return;
        }

        if ($previousStatus === ServiceStatus::DISABLED && $newStatus === ServiceStatus::STOPPED) {
            return;
        }

        $updated = Service::query()
            ->where('id', $service->id)
            ->where('status', $previousStatus)
            ->update(['status' => $newStatus]);

        if ($updated === 0) {
            return;
        }

        $service->refresh();

        ServiceStatusChanged::dispatch($service, $previousStatus, $newStatus);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'service.updated',
            data: new ServiceResource($service),
        ));
    }
}
