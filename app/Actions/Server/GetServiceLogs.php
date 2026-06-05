<?php

namespace App\Actions\Server;

use App\DTOs\ServiceLog;
use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Services\HasLogs;

class GetServiceLogs
{
    /**
     * @return array<int, ServiceLog>
     */
    public function handle(Server $server): array
    {
        $logs = [];

        $server->loadMissing('sites');

        $services = $server->services()
            ->where('status', ServiceStatus::READY)
            ->get();

        foreach ($services as $service) {
            $service->setRelation('server', $server);
            if (! $service->hasHandler()) {
                continue;
            }
            $handler = $service->handler();
            if (! $handler instanceof HasLogs) {
                continue;
            }
            foreach ($handler->logs() as $log) {
                $logs[] = $log;
            }
        }

        $logs[] = new ServiceLog(
            key: 'system:sshd',
            serviceLabel: 'System',
            label: 'SSH daemon journal',
            source: ServiceLog::SOURCE_JOURNAL,
            target: 'ssh.service',
        );

        return $logs;
    }

    public function resolve(Server $server, string $key): ?ServiceLog
    {
        foreach ($this->handle($server) as $log) {
            if ($log->key === $key) {
                return $log;
            }
        }

        return null;
    }
}
