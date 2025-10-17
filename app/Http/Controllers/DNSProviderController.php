<?php

namespace App\Http\Controllers;

use App\Actions\DNSProvider\CreateDNSProvider;
use App\Actions\DNSProvider\DeleteDNSProvider;
use App\Actions\DNSProvider\EditDNSProvider;
use App\Http\Resources\DNSProviderResource;
use App\Models\DNSProvider;
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

#[Prefix('settings/dns-providers')]
#[Middleware(['auth'])]
class DNSProviderController extends Controller
{
    #[Get('/', name: 'dns-providers')]
    public function index(): Response
    {
        $this->authorize('viewAny', DNSProvider::class);

        $user = user();

        return Inertia::render('dns-providers/index', [
            'dnsProviders' => DNSProviderResource::collection(DNSProvider::getByProjectId($user->current_project_id, $user)->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'dns-providers.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', DNSProvider::class);

        $user = user();

        return DNSProviderResource::collection(DNSProvider::getByProjectId($user->current_project_id, $user)->get());
    }

    #[Post('/', name: 'dns-providers.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DNSProvider::class);

        app(CreateDNSProvider::class)->create(user(), $request->all());

        return back()->with('success', 'DNS provider created.');
    }

    #[Patch('/{dnsProvider}', name: 'dns-providers.update')]
    public function update(Request $request, DNSProvider $dnsProvider): RedirectResponse
    {
        $this->authorize('update', $dnsProvider);

        app(EditDNSProvider::class)->edit($dnsProvider, $request->all());

        return back()->with('success', 'DNS provider updated.');
    }

    #[Delete('/{dnsProvider}', name: 'dns-providers.destroy')]
    public function destroy(DNSProvider $dnsProvider): RedirectResponse
    {
        $this->authorize('delete', $dnsProvider);

        app(DeleteDNSProvider::class)->delete($dnsProvider);

        return to_route('dns-providers')->with('success', 'DNS provider deleted.');
    }
}
