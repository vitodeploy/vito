<?php

namespace App\Actions\DNSProvider;

use App\DNSProviders\DNSProvider as DNSProviderContract;
use App\Models\DNSProvider;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateDNSProvider
{
    public function create(User $user, array $input): DNSProvider
    {
        $this->validate($input);

        $provider = self::getProvider($input['provider']);

        if (! $provider->connect($provider->credentialData($input))) {
            throw ValidationException::withMessages([
                'provider' => [
                    sprintf("Couldn't connect to %s. Please check your credentials.", $input['provider']),
                ],
            ]);
        }

        $dnsProvider = new DNSProvider;
        $dnsProvider->user_id = $user->id;
        $dnsProvider->name = $input['name'];
        $dnsProvider->provider = $input['provider'];
        $dnsProvider->credentials = $provider->credentialData($input);
        $dnsProvider->project_id = isset($input['global']) && $input['global'] ? null : $user->currentProject?->id;
        $dnsProvider->connected = true;
        $dnsProvider->save();

        return $dnsProvider;
    }

    private static function getProvider(string $name): DNSProviderContract
    {
        $providerClass = config('dns-provider.providers.'.$name.'.handler');
        /** @var DNSProviderContract $provider */
        $provider = new $providerClass(new DNSProvider);

        return $provider;
    }

    private function validate(array $input): void
    {
        $rules = [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(array_keys(config('dns-provider.providers'))),
            ],
        ];

        // Only get provider-specific rules if the provider exists
        if (isset($input['provider']) && config('dns-provider.providers.'.$input['provider'])) {
            $rules = array_merge($rules, $this->providerRules($input));
        }

        Validator::make($input, $rules)->validate();
    }

    private function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        return self::getProvider($input['provider'])->validationRules($input);
    }
}
