<?php

namespace App\Actions\Service;

use App\DTOs\SocketEventDTO;
use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Events\SocketEvent;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

class SyncServiceStatus
{
    public const array SETTLED_STATUSES = [
        ServiceStatus::READY,
        ServiceStatus::STOPPED,
        ServiceStatus::FAILED,
        ServiceStatus::DISABLED,
    ];

    public function sync(Server $server, Service $service, string $state, bool $force = false): bool
    {
        $newStatus = match ($state) {
            'active' => ServiceStatus::READY,
            'inactive' => ServiceStatus::STOPPED,
            'failed' => ServiceStatus::FAILED,
            default => null,
        };

        if (! $newStatus instanceof ServiceStatus) {
            return false;
        }

        $previousStatus = $service->status;

        if ($newStatus === $previousStatus
            || ($previousStatus === ServiceStatus::DISABLED && $newStatus === ServiceStatus::STOPPED)) {
            Cache::forget($this->pendingKey($service));

            return false;
        }

        if (! $force && $newStatus !== ServiceStatus::READY && ! $this->confirmed($service, $newStatus)) {
            return false;
        }

        Cache::forget($this->pendingKey($service));

        $updated = Service::query()
            ->where('id', $service->id)
            ->where('status', $previousStatus)
            ->update(['status' => $newStatus]);

        if ($updated === 0) {
            return false;
        }

        $service = $service->fresh();
        if (! $service instanceof Service) {
            return false;
        }

        ServiceStatusChanged::dispatch($service, $previousStatus, $newStatus);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'service.updated',
            data: new ServiceResource($service),
        ));

        return true;
    }

    private function pendingKey(Service $service): string
    {
        return "service-status-pending:{$service->id}";
    }

    private function confirmed(Service $service, ServiceStatus $newStatus): bool
    {
        $key = $this->pendingKey($service);

        if (Cache::get($key) === $newStatus->value) {
            return true;
        }

        Cache::put($key, $newStatus->value, now()->addHour());

        return false;
    }
}
