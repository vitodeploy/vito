<?php

namespace App\Services;

interface SupportsNetworking
{
    public function networkingEnabled(): bool;

    public function enableNetworking(): void;

    public function disableNetworking(): void;

    public function networkingPort(): int;

    /**
     * @return array<string, mixed>
     */
    public function networkingDetails(): array;
}
