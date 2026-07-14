<?php

namespace App\Actions\Service;

use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

class CheckServiceStatuses
{
    /**
     * @throws SSHError
     */
    public function check(Server $server): void
    {
        $lock = Cache::lock("unique-queue:server-{$server->id}", 60);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->poll($server);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws SSHError
     */
    private function poll(Server $server): void
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
            $handler = $service->handler();
            if (! $handler->canBeManaged()) {
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
            app(SyncServiceStatus::class)->sync($server, $service, $states[$index] ?? '');
        }
    }
}
