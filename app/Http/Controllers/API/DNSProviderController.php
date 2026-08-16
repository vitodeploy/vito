<?php

namespace App\Http\Controllers\API;

use App\Actions\DNSProvider\CreateDNSProvider;
use App\Actions\DNSProvider\DeleteDNSProvider;
use App\Actions\DNSProvider\EditDNSProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\DNSProviderResource;
use App\Models\DNSProvider;
use App\Support\TokenProjectScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/dns-providers')]
#[Middleware(['auth:sanctum'])]
class DNSProviderController extends Controller
{
    #[Get('/', name: 'api.dns-providers', middleware: 'ability:read')]
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', DNSProvider::class);

        $user = user();
        $dnsProviders = $user->dnsProviders();

        if (TokenProjectScope::restricted($user)) {
            $dnsProviders->where(function ($query) use ($user): void {
                $query->whereNull('project_id')
                    ->orWhereIn('project_id', TokenProjectScope::allowedProjectIds($user));
            });
        }

        return DNSProviderResource::collection($dnsProviders->simplePaginate(25));
    }

    #[Post('/', name: 'api.dns-providers.create', middleware: 'ability:write')]
    public function create(Request $request): DNSProviderResource
    {
        $this->authorize('create', DNSProvider::class);

        $user = user();
        $projectId = $user->currentProject?->id;

        abort_unless(TokenProjectScope::canCreate($user, $projectId, $request->boolean('global')), 403);

        $dnsProvider = app(CreateDNSProvider::class)->create($user, $request->all(), $projectId);

        return new DNSProviderResource($dnsProvider);
    }

    #[Get('{dnsProvider}', name: 'api.dns-providers.show', middleware: 'ability:read')]
    public function show(DNSProvider $dnsProvider): DNSProviderResource
    {
        $this->authorize('view', $dnsProvider);

        return new DNSProviderResource($dnsProvider);
    }

    #[Put('{dnsProvider}', name: 'api.dns-providers.update', middleware: 'ability:write')]
    public function update(Request $request, DNSProvider $dnsProvider): DNSProviderResource
    {
        $this->authorize('update', $dnsProvider);

        app(EditDNSProvider::class)->edit($dnsProvider, $request->all());

        return new DNSProviderResource($dnsProvider);
    }

    #[Delete('{dnsProvider}', name: 'api.dns-providers.destroy', middleware: 'ability:write')]
    public function destroy(DNSProvider $dnsProvider): JsonResponse
    {
        $this->authorize('delete', $dnsProvider);

        app(DeleteDNSProvider::class)->delete($dnsProvider);

        return response()->json(['message' => 'DNS provider deleted successfully']);
    }

    #[Get('{dnsProvider}/available', name: 'api.dns-providers.available', middleware: 'ability:read')]
    public function availableDomains(DNSProvider $dnsProvider): JsonResponse
    {
        $this->authorize('view', $dnsProvider);

        $domains = $dnsProvider->provider()->getDomains();

        return response()->json($domains);
    }
}
