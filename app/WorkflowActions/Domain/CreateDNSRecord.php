<?php

namespace App\WorkflowActions\Domain;

use App\Actions\Domain\CreateDNSRecord as CreateDNSRecordAction;
use App\DTOs\DynamicField;
use App\Models\Domain;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateDNSRecord extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            DynamicField::make('domain_id')->resource('domain')->label('Domain'),
            DynamicField::make('type')->select()->label('Type')->options(['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'SRV', 'NS', 'CAA', 'PTR', 'SOA'])->default('A')->half(),
            DynamicField::make('ttl')->text()->label('TTL')->placeholder('1')->description('Seconds (1-86400), 1 means automatic')->half(),
            DynamicField::make('name')->text()->label('Name')->placeholder('@')->description('Subdomain, or @ for the root domain'),
            DynamicField::make('content')->text()->label('Content')->placeholder('{server_ip}'),
            DynamicField::make('proxied')->checkbox()->label('Proxied through CDN')->default(false),
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
