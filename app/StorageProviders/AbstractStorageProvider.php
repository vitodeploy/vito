<?php

namespace App\StorageProviders;

use App\Models\StorageProvider;
use App\StorageProviders\StorageProvider as StorageProviderContract;

abstract class AbstractStorageProvider implements StorageProviderContract
{
    public function __construct(protected StorageProvider $storageProvider) {}

    /**
     * Credential keys that are safe to send back to the client for editing.
     *
     * @return array<int, string>
     */
    protected function editableFields(): array
    {
        return [];
    }

    /**
     * Credential keys that are never sent back to the client and are only
     * written when a non-empty value is submitted.
     *
     * @return array<int, string>
     */
    protected function secretFields(): array
    {
        return [];
    }

    /**
     * Extra validation rules merged on top of the default nullable rule.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function editFieldRules(): array
    {
        return [];
    }

    public function editableData(): array
    {
        $credentials = $this->storageProvider->credentials;
        $data = [];

        foreach ($this->editableFields() as $field) {
            $data[$field] = $credentials[$field] ?? '';
        }

        return $data;
    }

    public function editValidationRules(array $input): array
    {
        $extraRules = $this->editFieldRules();
        $rules = [];

        foreach ([...$this->editableFields(), ...$this->secretFields()] as $field) {
            $rules[$field] = array_merge(['nullable'], $extraRules[$field] ?? []);
        }

        return $rules;
    }

    public function mergeEditData(array $input): array
    {
        $credentials = $this->storageProvider->credentials;
        $needsReconnect = false;

        foreach ($this->editableFields() as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            if ($this->isUnchanged($credentials[$field] ?? null, $input[$field])) {
                continue;
            }

            $credentials[$field] = $input[$field];
            $needsReconnect = true;
        }

        foreach ($this->secretFields() as $field) {
            if (! isset($input[$field]) || $input[$field] === '') {
                continue;
            }

            $credentials[$field] = $input[$field];
            $needsReconnect = true;
        }

        return [$credentials, $needsReconnect];
    }

    private function isUnchanged(mixed $current, mixed $new): bool
    {
        if (is_bool($current) || is_bool($new)) {
            return (bool) $current === (bool) $new;
        }

        if (is_array($current) || is_array($new)) {
            return $current === $new;
        }

        return (string) ($current ?? '') === (string) ($new ?? '');
    }
}
