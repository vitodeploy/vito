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
        Validator::make($input, [
            'name' => [
                'required',
            ],
        ])->validate();

        $dnsProvider->name = $input['name'];
        $dnsProvider->connected = true;
        $dnsProvider->save();

        return $dnsProvider;
    }
}
