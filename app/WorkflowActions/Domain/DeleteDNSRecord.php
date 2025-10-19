<?php

namespace App\WorkflowActions\Domain;

use App\Actions\Domain\DeleteDNSRecord as DeleteDNSRecordAction;
use App\Models\DNSRecord;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;

class DeleteDNSRecord extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'dns_record_id' => 'The ID of the DNS record to delete',
        ];
    }

    public function outputs(): array
    {
        return [
            'success' => 'Whether the DNS record was deleted successfully',
            'deleted_record_id' => 'The ID of the deleted DNS record',
            'record_type' => 'The type of the deleted DNS record',
            'record_name' => 'The name of the deleted DNS record',
        ];
    }

    public function run(array $input): array
    {
        Validator::make($input, [
            'dns_record_id' => ['required', 'integer', 'exists:dns_records,id'],
        ])->validate();

        $dnsRecord = DNSRecord::query()->findOrFail($input['dns_record_id']);

        $this->authorize('update', $dnsRecord->domain);

        app(DeleteDNSRecordAction::class)->delete($dnsRecord);

        return [
            'success' => true,
            'deleted_record_id' => $dnsRecord->id,
            'record_type' => $dnsRecord->type,
            'record_name' => $dnsRecord->name,
        ];
    }
}
