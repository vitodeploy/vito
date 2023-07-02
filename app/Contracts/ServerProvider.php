<?php

namespace App\Contracts;

interface ServerProvider
{
    public function createValidationRules(array $input): array;

    public function credentialValidationRules(array $input): array;

    public function credentialData(array $input): array;

    public function data(array $input): array;

    public function connect(array $credentials = null): bool;

    public function plans(): array;

    public function regions(): array;

    public function create(): void;

    public function isRunning(): bool;

    public function delete(): void;
}
