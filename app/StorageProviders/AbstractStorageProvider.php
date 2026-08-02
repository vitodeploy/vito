<?php

namespace App\StorageProviders;

use App\Models\StorageProvider;
use App\StorageProviders\StorageProvider as StorageProviderContract;

abstract class AbstractStorageProvider implements StorageProviderContract
{
    public function __construct(protected StorageProvider $storageProvider) {}

    public static function editFields(): array
    {
        return [];
    }

    public function forgetCachedState(): void {}

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
        $createRules = $this->validationRules();
        $rules = [];

        $checkboxFields = static::checkboxFields();

        foreach ($this->editableFields() as $field) {
            $rules[$field] = ['sometimes', ...(array) ($createRules[$field] ?? 'nullable')];

            if (in_array($field, $checkboxFields, true)) {
                $rules[$field][] = 'boolean';
            }
        }

        foreach ($this->secretFields() as $field) {
            $optionalRules = array_filter(
                (array) ($createRules[$field] ?? []),
                fn (mixed $rule): bool => $rule !== 'required',
            );

            $rules[$field] = ['nullable', ...$optionalRules];
        }

        return $rules;
    }

    public function mergeEditData(array $input): array
    {
        $credentials = $this->storageProvider->credentials;
        $needsReconnect = false;
        $checkboxFields = static::checkboxFields();

        foreach ($this->editableFields() as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = in_array($field, $checkboxFields, true) ? (bool) $input[$field] : $input[$field];

            if ($this->isUnchanged($credentials[$field] ?? null, $value)) {
                continue;
            }

            $credentials[$field] = $value;
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

    /**
     * @return array<int, string>
     */
    protected static function checkboxFields(): array
    {
        $fields = [];

        foreach (static::editFields() as $field) {
            $config = $field->toArray();

            if (($config['type'] ?? null) === 'checkbox') {
                $fields[] = $config['name'];
            }
        }

        return $fields;
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
