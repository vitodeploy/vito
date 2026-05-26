<?php

namespace App\DNSProviders;

use App\Models\DNSProvider as DNSProviderModel;

abstract class AbstractDNSProvider implements DNSProvider
{
    public function __construct(protected DNSProviderModel $dnsProvider) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function validationRules(array $input): array
    {
        return [
            'token' => 'required',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function credentialData(array $input): array
    {
        return [
            'token' => $input['token'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function editableData(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: bool}
     */
    public function mergeEditData(array $input): array
    {
        return [$this->dnsProvider->credentials, false];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|array<int, mixed>>
     */
    public function editValidationRules(array $input): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDomains(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDomain(string $domainId): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecords(string $domainId): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $recordData
     * @return array<string, mixed>
     */
    public function createRecord(string $domainId, array $recordData): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $recordData
     * @return array<string, mixed>
     */
    public function updateRecord(string $domainId, string $recordId, array $recordData): array
    {
        return [];
    }

    public function deleteRecord(string $domainId, string $recordId): bool
    {
        return false;
    }
}
