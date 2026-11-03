<?php

namespace App\Actions\Domain;

use App\Models\DNSRecord;
use App\Models\EnvironmentDomain;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($dnsRecord): void {
            $dnsRecord->delete();
            if (in_array($dnsRecord->type, ['A', 'AAAA', 'CNAME'], true)) {
                EnvironmentDomain::query()->where('dns_domain_id', $dnsRecord->domain_id)->where('host', $dnsRecord->name)->update(['dns_domain_id' => null]);
            }
        });
    }
}
