<?php

namespace App\Services\MemoryDatabase;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use RuntimeException;

trait HasNetworking
{
    public function networkingEnabled(): bool
    {
        return (bool) ($this->service->type_data['networking'] ?? false);
    }

    public function networkingPort(): int
    {
        return 6379;
    }

    /**
     * @throws SSHError
     */
    public function enableNetworking(): void
    {
        $secret = $this->service->secret;

        if ($secret === null || $secret === '') {
            throw new RuntimeException("Networking password is missing for {$this->service->name}.");
        }

        $this->service->server->ssh()
            ->variables(['VITO_MEMDB_PASSWORD' => $secret])
            ->exec(
                view('ssh.services.memory-database.enable-networking', $this->networkingScriptData()),
                'enable-'.static::id().'-networking'
            );

        $this->restartForNetworking(true);
    }

    /**
     * @throws SSHError
     */
    public function disableNetworking(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.memory-database.disable-networking', $this->networkingScriptData()),
            'disable-'.static::id().'-networking'
        );

        $this->restartForNetworking(false);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SSHError
     */
    public function networkingDetails(): array
    {
        return [
            'enabled' => $this->networkingEnabled(),
            'port' => $this->networkingPort(),
            'secret' => $this->service->secret,
            'effective' => $this->service->status === ServiceStatus::RESTARTING ? null : $this->networkingIsOpen(),
        ];
    }

    /**
     * @throws SSHError
     */
    private function restartForNetworking(bool $expectedOpen): void
    {
        if ($this->service->status !== ServiceStatus::READY) {
            return;
        }

        try {
            if (! $this->manage('restart')) {
                throw new SSHCommandError("Failed to restart {$this->service->name} after updating networking.");
            }

            $this->verifyNetworking($expectedOpen);
        } catch (SSHError $e) {
            if ($expectedOpen) {
                $this->rollbackNetworking();
            }

            throw $e;
        }
    }

    private function rollbackNetworking(): void
    {
        try {
            $this->service->server->ssh()->exec(
                view('ssh.services.memory-database.rollback-networking', $this->networkingScriptData()),
                'rollback-'.static::id().'-networking'
            );

            $this->manage('restart');
        } catch (SSHError) {
        }
    }

    /**
     * @throws SSHError
     */
    private function verifyNetworking(bool $expectedOpen): void
    {
        if ($this->networkingIsOpen() === $expectedOpen) {
            return;
        }

        throw new SSHCommandError($expectedOpen
            ? "Networking is not active in the {$this->service->name} configuration after the restart."
            : "Networking is still active in the {$this->service->name} configuration after the restart.");
    }

    /**
     * @throws SSHError
     */
    private function networkingIsOpen(): bool
    {
        $bind = $this->service->server->ssh()->clearLog()->exec(
            sprintf("sudo grep -E '^[[:space:]]*bind' %s | tail -1 || true", $this->networkingConfPath())
        );

        return preg_match('/^\s*bind\s+(0\.0\.0\.0|\*)/m', $bind) === 1;
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
