<?php

namespace App\Http\Controllers\API;

use App\Actions\Domain\AddDomain;
use App\Actions\Domain\RemoveDomain;
use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\DNSProvider;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/domains')]
#[Middleware(['auth:sanctum'])]
class DomainController extends Controller
{
    #[Get('/', name: 'api.domains', middleware: 'ability:read')]
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', Domain::class);

        $domains = user()->domains()->with('dnsProvider')->simplePaginate(25);

        return DomainResource::collection($domains);
    }

    #[Post('/', name: 'api.domains.create', middleware: 'ability:write')]
    public function create(Request $request): DomainResource
    {
        $this->authorize('create', Domain::class);

        $user = user();
        $domain = app(AddDomain::class)->add($user, $request->all());

        return new DomainResource($domain->load('dnsProvider'));
    }

    #[Get('{domain}', name: 'api.domains.show', middleware: 'ability:read')]
    public function show(Domain $domain): DomainResource
    {
        $this->authorize('view', $domain);

        return new DomainResource($domain->load('dnsProvider'));
    }

    #[Delete('{domain}', name: 'api.domains.destroy', middleware: 'ability:write')]
    public function destroy(Domain $domain): JsonResponse
    {
        $this->authorize('delete', $domain);

        app(RemoveDomain::class)->remove($domain);

        return response()->json(['message' => 'Domain removed successfully']);
    }

    #[Get('{dnsProvider}/available', name: 'api.domains.available', middleware: 'ability:read')]
    public function availableDomains(DNSProvider $dnsProvider): JsonResponse
    {
        $this->authorize('view', $dnsProvider);

        $domains = $dnsProvider->provider()->getDomains();

        return response()->json($domains);
    }
}
