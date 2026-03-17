<?php

namespace App\DNSProviders;

interface DNSProvider
{
    public static function id(): string;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function validationRules(array $input): array;

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
     * Merge edit input into existing credentials, ignoring empty optional fields.
     * Returns [credentials, needsReconnect] tuple.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: bool}
     */
    public function mergeEditData(array $input): array;

    /**
     * Validation rules for edit form (fields are optional by default).
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
     * @return array<string, mixed>
     */
    public function getDomains(): array;

    /**
     * @return array<string, mixed>
     */
    public function getDomain(string $domainId): array;

    /**
     * @return array<string, mixed>
     */
    public function getRecords(string $domainId): array;

    /**
     * @param  array<string, mixed>  $recordData
     * @return array<string, mixed>
     */
    public function createRecord(string $domainId, array $recordData): array;

    /**
     * @param  array<string, mixed>  $recordData
     * @return array<string, mixed>
     */
    public function updateRecord(string $domainId, string $recordId, array $recordData): array;

    public function deleteRecord(string $domainId, string $recordId): bool;
}
