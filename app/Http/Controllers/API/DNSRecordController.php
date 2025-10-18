<?php

namespace App\Http\Controllers\API;

use App\Actions\Domain\CreateDNSRecord;
use App\Actions\Domain\DeleteDNSRecord;
use App\Actions\Domain\UpdateDNSRecord;
use App\Http\Controllers\Controller;
use App\Http\Resources\DNSRecordResource;
use App\Models\DNSRecord;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/domains/{domain}/records')]
#[Middleware(['auth:sanctum'])]
class DNSRecordController extends Controller
{
    #[Get('/', name: 'api.dns-records', middleware: 'ability:read')]
    public function index(Domain $domain): ResourceCollection
    {
        $this->authorize('view', $domain);

        $records = $domain->records()->orderBy('type')->orderBy('name')->get();

        return DNSRecordResource::collection($records);
    }

    #[Post('/', name: 'api.dns-records.create', middleware: 'ability:write')]
    public function create(Request $request, Domain $domain): DNSRecordResource
    {
        $this->authorize('update', $domain);

        $record = app(CreateDNSRecord::class)->create($domain, $request->all());

        return new DNSRecordResource($record);
    }

    #[Get('{dnsRecord}', name: 'api.dns-records.show', middleware: 'ability:read')]
    public function show(Domain $domain, DNSRecord $dnsRecord): DNSRecordResource
    {
        if ($dnsRecord->domain_id !== $domain->id) {
            abort(404);
        }

        $this->authorize('view', $domain);

        return new DNSRecordResource($dnsRecord);
    }

    #[Patch('{dnsRecord}', name: 'api.dns-records.update', middleware: 'ability:write')]
    public function update(Request $request, Domain $domain, DNSRecord $dnsRecord): DNSRecordResource
    {
        if ($dnsRecord->domain_id !== $domain->id) {
            abort(404);
        }

        $this->authorize('update', $domain);

        app(UpdateDNSRecord::class)->update($dnsRecord, $request->all());

        return new DNSRecordResource($dnsRecord);
    }

    #[Delete('{dnsRecord}', name: 'api.dns-records.destroy', middleware: 'ability:write')]
    public function destroy(Domain $domain, DNSRecord $dnsRecord): JsonResponse
    {
        if ($dnsRecord->domain_id !== $domain->id) {
            abort(404);
        }

        $this->authorize('update', $domain);

        app(DeleteDNSRecord::class)->delete($dnsRecord);

        return response()->json(['message' => 'DNS record deleted successfully']);
    }
}
