<?php

namespace App\Http\Controllers\API;

use App\Actions\Domain\CreateDNSRecord;
use App\Actions\Domain\DeleteDNSRecord;
use App\Actions\Domain\UpdateDNSRecord;
use App\Http\Controllers\Controller;
use App\Http\Resources\DNSRecordResource;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/domains/{domain}/records')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class DNSRecordController extends Controller
{
    #[Get('/', name: 'api.dns-records', middleware: 'ability:read')]
    public function index(Project $project, Domain $domain): ResourceCollection
    {
        $this->authorize('view', $domain);

        $this->validateRoute($project, $domain);

        $records = $domain->records()->orderBy('type')->orderBy('name')->get();

        return DNSRecordResource::collection($records);
    }

    #[Post('/', name: 'api.dns-records.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Domain $domain): DNSRecordResource
    {
        $this->authorize('update', $domain);

        $this->validateRoute($project, $domain);

        $record = app(CreateDNSRecord::class)->create($domain, $request->all());

        return new DNSRecordResource($record);
    }

    #[Get('{dnsRecord}', name: 'api.dns-records.show', middleware: 'ability:read')]
    public function show(Project $project, Domain $domain, DNSRecord $dnsRecord): DNSRecordResource
    {
        $this->authorize('view', $domain);

        $this->validateRoute($project, $domain);

        $this->validateRecord($domain, $dnsRecord);

        return new DNSRecordResource($dnsRecord);
    }

    #[Patch('{dnsRecord}', name: 'api.dns-records.update', middleware: 'ability:write')]
    public function update(Request $request, Project $project, Domain $domain, DNSRecord $dnsRecord): DNSRecordResource
    {
        $this->authorize('update', $domain);

        $this->validateRoute($project, $domain);

        $this->validateRecord($domain, $dnsRecord);

        app(UpdateDNSRecord::class)->update($dnsRecord, $request->all());

        return new DNSRecordResource($dnsRecord);
    }

    #[Delete('{dnsRecord}', name: 'api.dns-records.destroy', middleware: 'ability:write')]
    public function destroy(Project $project, Domain $domain, DNSRecord $dnsRecord): JsonResponse
    {
        $this->authorize('update', $domain);
        $this->validateRoute($project, $domain);
        $this->validateRecord($domain, $dnsRecord);

        app(DeleteDNSRecord::class)->delete($dnsRecord);

        return response()->json(['message' => 'DNS record deleted successfully']);
    }

    #[Post('/sync', name: 'api.dns-records.sync', middleware: 'ability:write')]
    public function sync(Project $project, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);
        $this->validateRoute($project, $domain);

        try {
            $domain->syncDnsRecords();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to sync DNS records: '.$e->getMessage()], 422);
        }

        return response()->json(['message' => 'DNS records synced successfully']);
    }

    private function validateRoute(Project $project, Domain $domain): void
    {
        if ($project->id !== $domain->project_id) {
            abort(404, 'Domain not found in project');
        }
    }

    private function validateRecord(Domain $domain, DNSRecord $dnsRecord): void
    {
        if ($dnsRecord->domain_id !== $domain->id) {
            abort(404, 'DNS record not found in domain');
        }
    }
}
