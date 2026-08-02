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

    /**
     * Non-sensitive credential data that can be exposed for editing.
     *
     * @return array<string, mixed>
     */
    public function editableData(): array;

    /**
     * Merge edit input into existing credentials, ignoring empty secret fields.
     * Returns [credentials, needsReconnect] tuple.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: bool}
     */
    public function mergeEditData(array $input): array;

    /**
     * Validation rules for the edit form (fields are optional by default).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string|array<int, mixed>>
     */
    public function editValidationRules(array $input): array;

    public function connect(): bool;

    public function ssh(Server $server): Storage;
}
