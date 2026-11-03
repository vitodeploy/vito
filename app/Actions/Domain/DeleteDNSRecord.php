<?php

namespace App\Actions\Domain;

use App\Models\DNSRecord;
use App\Models\EnvironmentDomain;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteDNSRecord
{
    /**
     * @throws ValidationException
     */
    public function delete(DNSRecord $dnsRecord): void
    {
        $provider = $dnsRecord->domain->dnsProvider->provider();

        try {
            $deleted = $provider->deleteRecord(
                $dnsRecord->domain->provider_domain_id,
                $dnsRecord->provider_record_id
            );
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'record' => [$e->getMessage()],
            ]);
        }

        if (! $deleted) {
            throw ValidationException::withMessages([
                'record' => ['The DNS provider could not delete the record.'],
            ]);
        }

        $dnsRecord->delete();
        EnvironmentDomain::query()->where('dns_domain_id', $dnsRecord->domain_id)->where('host', $dnsRecord->name)->update(['dns_domain_id' => null]);
    }
}
