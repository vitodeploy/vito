<?php

namespace App\Http\Controllers;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Http\Resources\ServerProviderResource;
use App\Models\ServerProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/server-providers')]
#[Middleware(['auth'])]
class ServerProviderController extends Controller
{
    public function index(): void {}

    #[Get('/', name: 'server-providers.all')]
    public function all(): ResourceCollection
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
}
