<?php

namespace App\Actions\Server;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Services\HasLogs;
use App\Services\ServiceLog;

class GetServiceLogs
{
    /**
     * @var array<int, array<int, ServiceLog>>
     */
    private array $cache = [];

    /**
     * @return array<int, ServiceLog>
     */
    public function handle(Server $server): array
    {
        if (isset($this->cache[$server->id])) {
            return $this->cache[$server->id];
        }

        $logs = [];

        $server->loadMissing('sites');

        $services = $server->services()
            ->where('status', ServiceStatus::READY)
            ->get();

        foreach ($services as $service) {
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

        return $this->cache[$server->id] = $logs;
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
