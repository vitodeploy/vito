<?php

namespace App\Actions\Domain;

use App\Models\DNSRecord;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateDNSRecord
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(DNSRecord $dnsRecord, array $input): DNSRecord
    {
        $this->validate($input);

        $provider = $dnsRecord->domain->dnsProvider->provider();

        try {
            $recordData = $provider->updateRecord(
                $dnsRecord->domain->provider_domain_id,
                $dnsRecord->provider_record_id,
                $input
            );
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'record' => [$e->getMessage()],
            ]);
        }

        $dnsRecord->type = $input['type'];
        $dnsRecord->name = $input['name'];
        $dnsRecord->content = $input['content'];
        $dnsRecord->ttl = $input['ttl'] ?? 1;
        $dnsRecord->proxied = $input['proxied'] ?? false;
        $dnsRecord->priority = $input['priority'] ?? null;
        $dnsRecord->metadata = $recordData;
        $dnsRecord->save();

        return $dnsRecord;
    }

    private function validate(array $input): void
    {
        Validator::make($input, DNSRecordRules::rules())->validate();
    }
}
