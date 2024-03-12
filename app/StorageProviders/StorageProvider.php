<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;

interface StorageProvider
{
    public function validationRules(): array;

    public function credentialData(array $input): array;

    public function connect(): bool;

    public function ssh(Server $server): Storage;

    public function delete(array $paths): void;
}
