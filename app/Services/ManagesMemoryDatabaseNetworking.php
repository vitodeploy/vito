<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Models\ServerLog;
use Illuminate\Support\Str;

trait ManagesMemoryDatabaseNetworking
{
    use ManagesNetworking;

    public function networkingPort(): int
    {
        return 6379;
    }

    public function networkingSecret(): ?string
    {
        return $this->service->secret;
    }

    public function prepareNetworking(): void
    {
        if ($this->service->secret === null || $this->service->secret === '') {
            $this->service->secret = $this->generateNetworkingSecret();
        }
    }

    public function generateNetworkingSecret(): string
    {
        return Str::random(32);
    }

    /**
     * @throws SSHError
     */
    public function writeNetworkingSecret(?string $secret): void
    {
        try {
            $ssh = $this->service->server->ssh();

            if ($secret !== null) {
                $ssh = $ssh->variables(['VITO_MEMDB_PASSWORD' => $secret]);
            }

            $ssh->exec(
                view('ssh.services.memory-database.write-secret', [
                    ...$this->networkingScriptData(),
                    'withSecret' => $secret !== null,
                ]),
                ($secret === null ? 'remove' : 'update').'-'.static::id().'-secret'
            );

            if ($this->service->status !== ServiceStatus::READY) {
                return;
            }

            if (! $this->manage('restart')) {
                throw new SSHCommandError("Failed to restart {$this->service->name} after updating the password.");
            }
        } catch (SSHError $e) {
            $this->rollbackNetworkingSecret();

            throw $e;
        }
    }

    private function rollbackNetworkingSecret(): void
    {
        try {
            $this->service->server->ssh()->exec(
                view('ssh.services.memory-database.rollback-secret', $this->networkingScriptData()),
                'rollback-'.static::id().'-secret'
            );

            if ($this->service->status === ServiceStatus::READY) {
                $this->manage('restart');
            }
        } catch (SSHError $e) {
            ServerLog::log(
                $this->service->server,
                'rollback-'.static::id().'-secret-failed',
                $e->getMessage()
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function networkingExtraDetails(): array
    {
        return [
            'uses_password' => true,
        ];
    }

    /**
     * @throws SSHError
     */
    protected function writeNetworkingConfig(bool $enable): void
    {
        if (! $enable) {
            $this->service->server->ssh()->exec(
                view('ssh.services.memory-database.disable-networking', $this->networkingScriptData()),
                'disable-'.static::id().'-networking'
            );

            return;
        }

        $secret = $this->service->secret;

        if ($secret === null || $secret === '') {
            throw new SSHCommandError("Networking password is missing for {$this->service->name}.");
        }

        $this->service->server->ssh()
            ->variables(['VITO_MEMDB_PASSWORD' => $secret])
            ->exec(
                view('ssh.services.memory-database.enable-networking', $this->networkingScriptData()),
                'enable-'.static::id().'-networking'
            );
    }

    /**
     * @throws SSHError
     */
    protected function runNetworkingRollback(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.memory-database.rollback-networking', $this->networkingScriptData()),
            'rollback-'.static::id().'-networking'
        );
    }

    /**
     * @throws SSHError
     */
    protected function verifyNetworking(bool $expectedOpen): void
    {
        if ($this->networkingIsOpen() === $expectedOpen) {
            return;
        }

        throw new SSHCommandError($expectedOpen
            ? "Networking is not active in the {$this->service->name} configuration after the restart."
            : "Networking is still active in the {$this->service->name} configuration after the restart.");
    }

    public function networkingProbeCommand(): string
    {
        return sprintf("sudo grep -E '^[[:space:]]*bind' %s | tail -1 || true", $this->networkingConfPath());
    }

    public function networkingProbeRequiresRunning(): bool
    {
        return false;
    }

    public function parseNetworkingProbe(string $output): ?bool
    {
        if (trim($output) === '') {
            return null;
        }

        return preg_match('/^\s*bind\s+(0\.0\.0\.0|\*)/m', $output) === 1;
    }

    /**
     * @return array<string, string>
     */
    private function networkingScriptData(): array
    {
        return [
            'conf' => $this->networkingConfPath(),
            'include' => $this->networkingIncludePath(),
            'owner' => static::id(),
        ];
    }

    private function networkingConfPath(): string
    {
        return sprintf('/etc/%s/%s.conf', static::id(), static::id());
    }

    private function networkingIncludePath(): string
    {
        return sprintf('/etc/%s/vito-networking.conf', static::id());
    }
}
