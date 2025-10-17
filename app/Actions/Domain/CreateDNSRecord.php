<?php

namespace App\Actions\Domain;

use App\Models\DNSRecord;
use App\Models\Domain;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateDNSRecord
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Domain $domain, array $input): DNSRecord
    {
        $this->validate($input);

        $provider = $domain->dnsProvider->provider();

        try {
            $recordData = $provider->createRecord($domain->provider_domain_id, $input);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'record' => [$e->getMessage()],
            ]);
        }

        $dnsRecord = new DNSRecord;
        $dnsRecord->domain_id = $domain->id;
        $dnsRecord->type = $input['type'];
        $dnsRecord->name = $input['name'];
        $dnsRecord->content = $input['content'];
        $dnsRecord->ttl = $input['ttl'] ?? 1;
        $dnsRecord->proxied = $input['proxied'] ?? false;
        $dnsRecord->provider_record_id = $recordData['id'];
        $dnsRecord->metadata = $recordData;
        $dnsRecord->save();

        return $dnsRecord;
    }

    private function validate(array $input): void
    {
        $rules = [
            'type' => [
                'required',
                Rule::in([
                    'A',
                    'AAAA',
                    'CNAME',
                    'TXT',
                    'MX',
                    'SRV',
                    'NS',
                    'CAA',
                    'PTR',
                    'SOA',
                ]),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
            ],
            'ttl' => [
                'integer',
                'min:1',
                'max:86400',
            ],
            'proxied' => [
                'boolean',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
