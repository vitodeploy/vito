<?php

namespace App\Services\Database;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;

trait ManagesMysqlNetworking
{
    use ManagesDatabaseNetworking;

    public function networkingPort(): int
    {
        return 3306;
    }

    protected function getNetworkingScriptView(string $script): string
    {
        return 'ssh.services.database.mysql-family.'.$script;
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
                'managesXPlugin' => $this->networkingManagesXPlugin(),
            ]),
            ($enable ? 'enable' : 'disable').'-'.static::id().'-networking'
        );
    }

    /**
     * @throws SSHError
     */
    protected function runNetworkingRollback(): void
    {
        $this->service->server->ssh()->exec(
            view($this->getNetworkingScriptView('rollback-networking'), $this->networkingScriptData()),
            'rollback-'.static::id().'-networking'
        );
    }

    /**
     * @throws SSHError
     */
    protected function verifyNetworking(bool $expectedOpen): void
    {
        $expected = $expectedOpen ? '0.0.0.0' : '127.0.0.1';

        if (! $this->networkingValueMatches($this->networkingBindAddress(), $expected)) {
            throw new SSHCommandError("{$this->service->name} is not bound to {$expected} after the restart.");
        }

        $this->verifyXPluginNetworking($expected);
    }

    public function networkingProbeCommand(): string
    {
        return sprintf('timeout 10 sudo %s -N -e "SELECT @@bind_address"', static::id());
    }

    public function parseNetworkingProbe(string $output): ?bool
    {
        if (trim($output) === '') {
            return null;
        }

        return $this->networkingValueMatches($output, '0.0.0.0', '*', '::');
    }

    abstract protected function networkingManagesXPlugin(): bool;

    /**
     * @throws SSHError
     */
    private function verifyXPluginNetworking(string $expected): void
    {
        if (! $this->networkingManagesXPlugin()) {
            return;
        }

        $output = $this->service->server->ssh()->clearLog()->exec(
            sprintf('sudo %s -N -e "SHOW VARIABLES LIKE \'mysqlx_bind_address\'"', static::id())
        );

        if (preg_match('/^\s*mysqlx_bind_address\s+(\S+)\s*$/m', $output, $matches) !== 1) {
            return;
        }

        if ($matches[1] !== $expected) {
            throw new SSHCommandError("The {$this->service->name} X plugin is not bound to {$expected} after the restart.");
        }
    }

    /**
     * @throws SSHError
     */
    private function networkingBindAddress(): string
    {
        return $this->service->server->ssh()->clearLog()->exec($this->networkingProbeCommand());
    }

    /**
     * @return array<string, string>
     */
    private function networkingScriptData(): array
    {
        $directory = sprintf('/etc/mysql/%s.conf.d', static::id());

        return [
            'directory' => $directory,
            'dropIn' => $directory.'/zz-vito-networking.cnf',
        ];
    }
}
