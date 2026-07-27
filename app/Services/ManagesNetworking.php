<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Models\ServerLog;

trait ManagesNetworking
{
    public function networkingEnabled(): bool
    {
        return (bool) ($this->service->type_data['networking'] ?? false);
    }

    public function networkingManaged(): bool
    {
        return array_key_exists('networking', $this->service->type_data ?? []);
    }

    public function networkingFailed(): bool
    {
        return (bool) ($this->service->type_data['networking_failed'] ?? false);
    }

    public function networkingSecret(): ?string
    {
        return null;
    }

    public function prepareNetworking(): void {}

    /**
     * @throws SSHError
     */
    public function enableNetworking(): void
    {
        $this->applyNetworking(true);
    }

    /**
     * @throws SSHError
     */
    public function disableNetworking(): void
    {
        $this->applyNetworking(false);
    }

    /**
     * @return array<string, mixed>
     */
    public function networkingDetails(): array
    {
        return [
            'enabled' => $this->networkingEnabled(),
            'managed' => $this->networkingManaged(),
            'failed' => $this->networkingFailed(),
            'port' => $this->networkingPort(),
            'effective' => $this->service->type_data['networking_effective'] ?? null,
            'checked_at' => $this->service->type_data['networking_checked_at'] ?? null,
            ...$this->networkingExtraDetails(),
        ];
    }

    public function networkingProbeRequiresRunning(): bool
    {
        return true;
    }

    public function rememberEffectiveNetworking(?bool $effective, bool $observed = true): void
    {
        $this->service->jsonUpdate('type_data', 'networking_effective', $effective, save: false);

        if ($observed) {
            $this->service->jsonUpdate('type_data', 'networking_checked_at', now()->toIso8601String(), save: false);
        }
    }

    /**
     * @throws SSHError
     */
    private function applyNetworking(bool $enable): void
    {
        try {
            $this->writeNetworkingConfig($enable);

            if ($this->service->status !== ServiceStatus::READY) {
                return;
            }

            if (! $this->manage('restart')) {
                throw new SSHCommandError("Failed to restart {$this->service->name} after updating networking.");
            }

            $this->verifyNetworking($enable);
        } catch (SSHError $e) {
            if ($enable) {
                $this->rollbackNetworking();
            }

            throw $e;
        }
    }

    private function rollbackNetworking(): void
    {
        try {
            $this->runNetworkingRollback();
        } catch (SSHError $e) {
            $this->logFailedRollback($e);

            return;
        }

        if ($this->service->status !== ServiceStatus::READY) {
            return;
        }

        try {
            $this->manage('restart');
        } catch (SSHError $e) {
            $this->logFailedRollback($e);
        }
    }

    private function logFailedRollback(SSHError $e): void
    {
        ServerLog::log(
            $this->service->server,
            'rollback-'.static::id().'-networking-failed',
            $e->getMessage()
        );
    }

    abstract public static function id(): string;

    abstract public function manage(string $action): bool;

    abstract public function networkingPort(): int;

    /**
     * @return array<string, mixed>
     */
    abstract protected function networkingExtraDetails(): array;

    /**
     * @throws SSHError
     */
    abstract protected function writeNetworkingConfig(bool $enable): void;

    /**
     * @throws SSHError
     */
    abstract protected function runNetworkingRollback(): void;

    /**
     * @throws SSHError
     */
    abstract protected function verifyNetworking(bool $expectedOpen): void;

    abstract public function networkingProbeCommand(): string;

    abstract public function parseNetworkingProbe(string $output): ?bool;

    /**
     * @throws SSHError
     */
    protected function networkingIsOpen(): bool
    {
        $output = $this->service->server->ssh()->clearLog()->exec($this->networkingProbeCommand());

        return $this->parseNetworkingProbe($output) ?? false;
    }
}
