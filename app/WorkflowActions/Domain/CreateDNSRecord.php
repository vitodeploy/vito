<?php

namespace App\WorkflowActions\Domain;

use App\Actions\Domain\CreateDNSRecord as CreateDNSRecordAction;
use App\Models\Domain;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateDNSRecord extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'domain_id' => 'The ID of the domain to create the DNS record for',
            'type' => 'DNS record type (A, AAAA, CNAME, TXT, MX, SRV, NS, CAA, PTR, SOA)',
            'name' => 'DNS record name (subdomain or @ for root)',
            'content' => 'DNS record content/value',
            'ttl' => 'Time to live in seconds (1-86400, default: 1)',
            'proxied' => 'Whether the record is proxied through CDN (boolean, default: false)',
        ];
    }

    public function outputs(): array
    {
        return [
            'dns_record_id' => 'The ID of the created DNS record',
            'provider_record_id' => 'The provider-specific record ID',
            'record_type' => 'The DNS record type',
            'record_name' => 'The DNS record name',
            'record_content' => 'The DNS record content',
            'success' => 'Whether the DNS record was created successfully',
        ];
    }

    public function run(array $input): array
    {
        $domain = Domain::query()->findOrFail($input['domain_id']);

        $this->authorize('update', $domain);

        $dnsRecord = app(CreateDNSRecordAction::class)->create($domain, [
            'type' => $input['type'],
            'name' => $input['name'],
            'content' => $input['content'],
            'ttl' => $input['ttl'] ?? 1,
            'proxied' => $input['proxied'] ?? false,
        ]);

        return [
            'dns_record_id' => $dnsRecord->id,
            'provider_record_id' => $dnsRecord->provider_record_id,
            'record_type' => $dnsRecord->type,
            'record_name' => $dnsRecord->name,
            'record_content' => $dnsRecord->content,
            'success' => true,
        ];
    }
}
