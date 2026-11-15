<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
use App\Models\Server;
use App\SSH\Storage\Storage;

interface StorageProvider
{
    public static function id(): string;

    /**
     * @return array<int, DynamicField>
     */
    public static function editFields(): array;

    /**
     * @return array<string, string>
     */
    public function validationRules(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function credentialData(array $input): array;

    /**
     * @return array<string, mixed>
     */
    public function editableData(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: bool}
     */
    public function mergeEditData(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|array<int, mixed>>
     */
    public function editValidationRules(array $input): array;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function connect(array $credentials): bool;

    public function forgetCachedState(): void;

    public function ssh(Server $server): Storage;
}
