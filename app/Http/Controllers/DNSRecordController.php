<?php

namespace App\Http\Controllers;

use App\Actions\Domain\CreateDNSRecord;
use App\Actions\Domain\DeleteDNSRecord;
use App\Actions\Domain\UpdateDNSRecord;
use App\Http\Resources\DNSRecordResource;
use App\Http\Resources\DomainResource;
use App\Models\DNSRecord;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('domains/{domain}/records')]
#[Middleware(['auth', 'has-project'])]
class DNSRecordController extends Controller
{
    #[Get('/', name: 'dns-records.index')]
    public function index(Domain $domain): Response
    {
        $this->authorize('view', $domain);

        return Inertia::render('domains/show', [
            'domain' => new DomainResource($domain->load('dnsProvider')),
            'records' => DNSRecordResource::collection($domain->records()->orderByDesc('id')->get()),
        ]);
    }

    #[Get('/json', name: 'dns-records.json')]
    public function json(Domain $domain): ResourceCollection
    {
        $this->authorize('view', $domain);

        return DNSRecordResource::collection($domain->records()->orderBy('type')->orderBy('name')->get());
    }

    #[Post('/', name: 'dns-records.store')]
    public function store(Request $request, Domain $domain): RedirectResponse
    {
        $this->authorize('update', $domain);

        app(CreateDNSRecord::class)->create($domain, $request->all());

        return back()->with('success', 'DNS record created.');
    }

    #[Patch('/{dnsRecord}', name: 'dns-records.update')]
    public function update(Request $request, Domain $domain, DNSRecord $dnsRecord): RedirectResponse
    {
        if ($dnsRecord->domain_id !== $domain->id) {
            abort(404);
        }

        $this->authorize('update', $domain);

        app(UpdateDNSRecord::class)->update($dnsRecord, $request->all());

        return back()->with('success', 'DNS record updated.');
    }

    #[Delete('/{dnsRecord}', name: 'dns-records.destroy')]
    public function destroy(Domain $domain, DNSRecord $dnsRecord): RedirectResponse
    {
        if ($dnsRecord->domain_id !== $domain->id) {
            abort(404);
        }

        $this->authorize('update', $domain);

        app(DeleteDNSRecord::class)->delete($dnsRecord);

        return back()->with('success', 'DNS record deleted.');
    }

    #[Post('/sync', name: 'dns-records.sync')]
    public function sync(Domain $domain): RedirectResponse
    {
        $this->authorize('update', $domain);

        try {
            $domain->syncDnsRecords();
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to sync DNS records: '.$e->getMessage());
        }

        return back()->with('success', 'DNS records synced successfully.');
    }
}
