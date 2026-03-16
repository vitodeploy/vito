<?php

namespace App\Actions\DNSProvider;

use App\Models\DNSProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditDNSProvider
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(DNSProvider $dnsProvider, array $input): DNSProvider
    {
        $provider = $dnsProvider->provider();

        $rules = array_merge(
            ['name' => ['required']],
            $provider->editValidationRules($input),
        );

        Validator::make($input, $rules)->validate();

        $dnsProvider->name = $input['name'];
        $dnsProvider->project_id = isset($input['global']) && $input['global'] ? null : $dnsProvider->user->currentProject?->id;

        [$newCredentials, $needsReconnect] = $provider->mergeEditData($input);

        if ($needsReconnect) {
            if (! $provider->connect($newCredentials)) {
                throw ValidationException::withMessages([
                    'provider' => [sprintf("Couldn't connect to %s. Please check your credentials.", $dnsProvider->provider)],
                ]);
            }
        }

        if ($newCredentials !== $dnsProvider->credentials) {
            $dnsProvider->credentials = $newCredentials;
        }

        $dnsProvider->connected = true;
        $dnsProvider->save();

        return $dnsProvider;
    }
}
