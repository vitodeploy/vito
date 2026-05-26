<?php

namespace App\Http\Controllers;

use App\Actions\Domain\AddDomain;
use App\Actions\Domain\GetAvailableDomains;
use App\Actions\Domain\RemoveDomain;
use App\Http\Resources\DNSRecordResource;
use App\Http\Resources\DomainResource;
use App\Models\DNSProvider;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('domains')]
#[Middleware(['auth', 'has-project'])]
class DomainController extends Controller
{
    #[Get('/', name: 'domains')]
    public function index(): Response
    {
        $user = user();

        $this->authorize('viewAny', [Domain::class, $user->currentProject]);

        $domains = $user->currentProject->domains()->latest()->with('dnsProvider')->simplePaginate(config('web.pagination_size'));

        return Inertia::render('domains/index', [
            'domains' => DomainResource::collection($domains),
            'dnsProviders' => DNSProvider::getByProjectId($user->current_project_id, $user)->where('connected', true)->get(),
        ]);
    }

    #[Get('/json', name: 'domains.json')]
    public function json(): ResourceCollection
    {
        $user = user();

        $this->authorize('viewAny', [Domain::class, $user->currentProject]);

        $domains = $user->currentProject->domains()->with('dnsProvider')->orderByDesc('id')->get();

        return DomainResource::collection($domains);
    }

    #[Get('/{dnsProvider}/available', name: 'domains.available')]
    public function availableDomains(DNSProvider $dnsProvider): JsonResponse
    {
        $this->authorize('view', $dnsProvider);

        $domains = app(GetAvailableDomains::class)->execute($dnsProvider);

        return response()->json($domains);
    }

    #[Get('/{dnsProvider}/refresh', name: 'domains.refresh')]
    public function refreshDomains(DNSProvider $dnsProvider): JsonResponse
    {
        $this->authorize('view', $dnsProvider);

        $domains = app(GetAvailableDomains::class)->execute($dnsProvider, false);

        return response()->json($domains);
    }

    #[Post('/', name: 'domains.store')]
    public function store(Request $request): RedirectResponse
    {
        $user = user();

        $this->authorize('create', [Domain::class, $user->currentProject]);

        app(AddDomain::class)->add($user, $user->currentProject, $request->all());

        return back()->with('success', 'Domain added.');
    }

    #[Get('/{domain}', name: 'domains.show')]
    public function show(Domain $domain): Response
    {
        $this->authorize('view', $domain);

        return Inertia::render('domains/show', [
            'domain' => new DomainResource($domain->load('dnsProvider')),
            'records' => DNSRecordResource::collection($domain->records()->orderBy('type')->orderBy('name')->get()),
        ]);
    }

    #[Delete('/{domain}', name: 'domains.destroy')]
    public function destroy(Domain $domain): RedirectResponse
    {
        $this->authorize('delete', $domain);

        app(RemoveDomain::class)->remove($domain);

        return to_route('domains')->with('success', 'Domain removed.');
    }
}
