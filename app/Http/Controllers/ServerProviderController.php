<?php

namespace App\Http\Controllers;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Actions\ServerProvider\EditServerProvider;
use App\Http\Resources\ServerProviderResource;
use App\Models\ServerProvider;
use Illuminate\Http\JsonResponse;
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

#[Prefix('settings/server-providers')]
#[Middleware(['auth'])]
class ServerProviderController extends Controller
{
    #[Get('/', name: 'server-providers')]
    public function index(): Response
    {
        $this->authorize('viewAny', ServerProvider::class);

        return Inertia::render('server-providers/index', [
            'serverProviders' => ServerProviderResource::collection(ServerProvider::getByProjectId(user()->current_project_id)->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'server-providers.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', ServerProvider::class);

        return ServerProviderResource::collection(ServerProvider::getByProjectId(user()->current_project_id)->get());
    }

    #[Post('/', name: 'server-providers.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ServerProvider::class);

        app(CreateServerProvider::class)->create(user(), user()->currentProject, $request->all());

        return back()->with('success', 'Server provider created.');
    }

    #[Patch('/{serverProvider}', name: 'server-providers.update')]
    public function update(Request $request, ServerProvider $serverProvider): RedirectResponse
    {
        $this->authorize('update', $serverProvider);

        app(EditServerProvider::class)->edit($serverProvider, user()->currentProject, $request->all());

        return back()->with('success', 'Server provider updated.');
    }

    #[Get('/{serverProvider}/regions', name: 'server-providers.regions')]
    public function regions(ServerProvider $serverProvider): JsonResponse
    {
        $this->authorize('view', $serverProvider);

        return response()->json($serverProvider->provider()->regions());
    }

    #[Get('{serverProvider}/regions/{region}/plans', name: 'server-providers.plans')]
    public function plans(ServerProvider $serverProvider, string $region): JsonResponse
    {
        $this->authorize('view', $serverProvider);

        return response()->json($serverProvider->provider()->plans($region));
    }

    #[Delete('{serverProvider}', name: 'server-providers.destroy')]
    public function destroy(ServerProvider $serverProvider): RedirectResponse
    {
        $this->authorize('delete', $serverProvider);

        app(DeleteServerProvider::class)->delete($serverProvider);

        return to_route('server-providers')->with('success', 'Server provider deleted.');
    }
}
