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
