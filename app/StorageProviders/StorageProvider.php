<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;

interface StorageProvider
{
    public static function id(): string;

    /**
     * @return array<string, string>
     */
    public function validationRules(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function credentialData(array $input): array;

    public function connect(): bool;

    public function ssh(Server $server): Storage;
}
