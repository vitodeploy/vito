<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Services\HasLogs;
use App\Services\SupportsNetworking;
use Illuminate\Contracts\View\View;

class Clickhouse extends AbstractDatabase implements HasLogs, SupportsNetworking
{
    use ManagesDatabaseNetworking;

    protected array $systemDbs = ['system', 'information_schema', 'INFORMATION_SCHEMA'];

    protected array $systemUsers = ['default'];

    protected string $defaultCharset = 'UTF-8';

    protected int $headerLines = 1;

    public function usesHost(): bool
    {
        return false;
    }

    public function databaseUserExists(Server $server, string $username, string $host, ?DatabaseUser $ignore = null): bool
    {
        return $server->databaseUsers()
            ->where('username', $username)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();
    }

    public static function id(): string
    {
        return 'clickhouse';
    }

    public static function type(): string
    {
        return 'database';
    }

    public function unit(): string
    {
        return 'clickhouse-server';
    }

    protected function installScript(): View
    {
        return view($this->getScriptView('install'), [
            'version' => $this->service->version,
        ]);
    }

    public function versionCommand(): ?string
    {
        return 'clickhouse-client --version | grep -oE \'[0-9]+\.[0-9]+(\.[0-9]+)?\' | head -n 1';
    }

    public function networkingPort(): int
    {
        return 9000;
    }

    /**
     * @throws SSHError
     */
    protected function writeNetworkingConfig(bool $enable): void
    {
        $this->service->server->ssh()->exec(
            view($this->getNetworkingScriptView('write-networking'), [
                ...$this->networkingScriptData(),
                'address' => $enable ? '0.0.0.0' : '127.0.0.1',
            ]),
            ($enable ? 'enable' : 'disable').'-clickhouse-networking'
        );
    }

    /**
     * @throws SSHError
     */
    protected function runNetworkingRollback(): void
    {
        $this->service->server->ssh()->exec(
            view($this->getNetworkingScriptView('rollback-networking'), $this->networkingScriptData()),
            'rollback-clickhouse-networking'
        );
    }

    /**
     * @throws SSHError
     */
    protected function verifyNetworking(bool $expectedOpen): void
    {
        $expected = $expectedOpen ? '0.0.0.0' : '127.0.0.1';

        if (! $this->networkingValueMatches($this->networkingListenAddress(), $expected)) {
            throw new SSHCommandError("{$this->service->name} is not bound to {$expected} after the restart.");
        }
    }

    public function networkingProbeCommand(): string
    {
        return 'timeout 10 sudo clickhouse-client -q "SELECT value FROM system.server_settings WHERE name = \'listen_host\'"';
    }

    public function parseNetworkingProbe(string $output): ?bool
    {
        if (trim($output) === '') {
            return null;
        }

        return $this->networkingValueMatches($output, '0.0.0.0', '*', '::');
    }

    /**
     * @throws SSHError
     */
    private function networkingListenAddress(): string
    {
        return $this->service->server->ssh()->clearLog()->exec($this->networkingProbeCommand());
    }

    /**
     * @return array<string, string>
     */
    private function networkingScriptData(): array
    {
        $directory = '/etc/clickhouse-server/config.d';

        return [
            'directory' => $directory,
            'dropIn' => $directory.'/zz-vito-networking.xml',
        ];
    }

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'clickhouse:journal',
                serviceLabel: 'ClickHouse',
                label: 'Service journal',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'clickhouse-server.service',
            ),
        ];
    }
}
