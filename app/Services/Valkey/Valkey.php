<?php

namespace App\Services\Valkey;

use App\DTOs\ServiceLog;
use App\Exceptions\ServiceInstallationFailed;
use App\Exceptions\SSHError;
use App\Services\AbstractService;
use App\Services\HasLogs;
use App\Services\ManagesMemoryDatabaseNetworking;
use App\Services\SupportsNetworkingSecret;
use Closure;

class Valkey extends AbstractService implements HasLogs, SupportsNetworkingSecret
{
    use ManagesMemoryDatabaseNetworking;

    public static function id(): string
    {
        return 'valkey';
    }

    public static function type(): string
    {
        return 'memory_database';
    }

    public function unit(): string
    {
        return 'valkey-server';
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $memoryDatabaseExists = $this->service->server->memoryDatabase();
                    if ($memoryDatabaseExists) {
                        $fail('You already have a memory database service on the server.');
                    }
                },
            ],
        ];
    }

    /**
     * @throws ServiceInstallationFailed
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.valkey.install'),
                'install-valkey'
            );
        $status = $this->service->server->systemd()->status($this->unit());
        $this->service->validateInstall($status);
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.valkey.uninstall'),
            'uninstall-valkey'
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function versionCommand(): ?string
    {
        return "valkey-server --version | grep -oP 'v=\\K[0-9.]+'";
    }

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'valkey:journal',
                serviceLabel: 'Valkey',
                label: 'Service journal',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'valkey-server.service',
            ),
        ];
    }
}
