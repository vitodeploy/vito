<?php

namespace App\Services;

use App\Exceptions\SSHError;

interface SupportsNetworking
{
    public function networkingEnabled(): bool;

    public function networkingManaged(): bool;

    public function networkingFailed(): bool;

    public function networkingSecret(): ?string;

    public function prepareNetworking(): void;

    /**
     * @throws SSHError
     */
    public function enableNetworking(): void;

    /**
     * @throws SSHError
     */
    public function disableNetworking(): void;

    public function networkingPort(): int;

    /**
     * @return array<string, mixed>
     */
    public function networkingDetails(): array;

    public function networkingProbeCommand(): string;

    public function networkingProbeRequiresRunning(): bool;

    public function parseNetworkingProbe(string $output): ?bool;

    public function rememberEffectiveNetworking(?bool $effective, bool $observed = true): void;
}
