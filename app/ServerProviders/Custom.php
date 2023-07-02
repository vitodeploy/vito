<?php

namespace App\ServerProviders;

use App\ValidationRules\RestrictedIPAddressesRule;
use Illuminate\Validation\Rule;

class Custom extends AbstractProvider
{
    public function createValidationRules(array $input): array
    {
        return [
            'ip' => [
                'required',
                'ip',
                Rule::unique('servers', 'ip'),
                new RestrictedIPAddressesRule(),
            ],
            'port' => [
                'required',
                'numeric',
                'min:1',
                'max:65535',
            ],
        ];
    }

    public function credentialValidationRules(array $input): array
    {
        return [];
    }

    public function credentialData(array $input): array
    {
        return [];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function connect(array $credentials = null): bool
    {
        return true;
    }

    public function plans(): array
    {
        return [];
    }

    public function regions(): array
    {
        return [];
    }

    public function create(): void
    {
        $this->generateKeyPair();
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function delete(): void
    {
        //
    }
}
