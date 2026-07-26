<?php

namespace App\Services\Database;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;

trait HasNetworking
{
    public function networkingEnabled(): bool
    {
        return (bool) ($this->service->type_data['networking'] ?? false);
    }

    /**
     * @throws SSHError
     */
    public function enableNetworking(): void
    {
        $this->writeNetworkingConfig(true);

        $this->restartForNetworking(true);
    }

    /**
     * @throws SSHError
     */
    public function disableNetworking(): void
    {
        $this->writeNetworkingConfig(false);

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
            'requires_remote_users' => $this->usesHost(),
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
            $this->runNetworkingRollback();

            $this->manage('restart');
        } catch (SSHError) {
        }
    }

    protected function networkingValueMatches(string $output, string ...$expected): bool
    {
        foreach ($expected as $value) {
            if (preg_match('/^\s*'.preg_quote($value, '/').'\s*$/m', $output) === 1) {
                return true;
            }
        }

        return false;
    }

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

    /**
     * @throws SSHError
     */
    abstract protected function networkingIsOpen(): bool;
}
