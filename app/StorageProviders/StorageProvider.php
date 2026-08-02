<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
use App\Models\Server;
use App\SSH\Storage\Storage;

interface StorageProvider
{
    public static function id(): string;

    /**
     * Fields rendered by the edit form. Secret fields should be declared here
     * too, so they can be replaced without ever being sent back to the client.
     *
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

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function connect(array $credentials): bool;

    /**
     * Drop any state the provider caches outside the model, so a credential
     * change cannot be served from a stale cache.
     */
    public function forgetCachedState(): void;

    public function ssh(Server $server): Storage;
}
