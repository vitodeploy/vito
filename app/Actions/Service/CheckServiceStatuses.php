<?php

namespace App\Actions\Service;

use App\Exceptions\SSHError;
use App\Models\Server;

class CheckServiceStatuses
{
    /**
     * @throws SSHError
     */
    public function check(Server $server): void
    {
        $services = $server->services()
            ->whereIn('status', SyncServiceStatus::SETTLED_STATUSES)
            ->get();

        $checkable = [];
        $units = [];
        foreach ($services as $service) {
            if (! $service->hasHandler()) {
                continue;
            }
            $unit = $service->handler()->unit();
            if ($unit === '') {
                continue;
            }
            $checkable[] = $service;
            $units[] = $unit;
        }

        if ($units === []) {
            return;
        }

        $states = $server->systemd()->activeStates($units);

        if ($states === []) {
            return;
        }

        foreach ($checkable as $index => $service) {
            app(SyncServiceStatus::class)->sync($server, $service, $states[$index] ?? '');
        }
    }
}
