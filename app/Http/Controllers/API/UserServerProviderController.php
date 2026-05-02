<?php

namespace App\Http\Controllers\API;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Actions\ServerProvider\EditServerProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerProviderResource;
use App\Models\ServerProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/server-providers')]
#[Middleware(['auth:sanctum'])]
class UserServerProviderController extends Controller
{
    #[Get('/', name: 'api.user.server-providers', middleware: 'ability:read')]
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', ServerProvider::class);

        $serverProviders = user()->serverProviders()->simplePaginate(25);

        return ServerProviderResource::collection($serverProviders);
    }

    #[Post('/', name: 'api.user.server-providers.create', middleware: 'ability:write')]
    public function create(Request $request): ServerProviderResource
    {
        $this->authorize('create', ServerProvider::class);

        $user = user();
        $serverProvider = app(CreateServerProvider::class)->create($user, $request->all());

        return new ServerProviderResource($serverProvider);
    }

    #[Get('{serverProvider}', name: 'api.user.server-providers.show', middleware: 'ability:read')]
    public function show(ServerProvider $serverProvider): ServerProviderResource
    {
        $this->authorize('view', $serverProvider);

        // Ensure the server provider belongs to the authenticated user
        if ($serverProvider->user_id !== user()->id) {
            abort(404, 'Server provider not found');
        }

        return new ServerProviderResource($serverProvider);
    }

    #[Put('{serverProvider}', name: 'api.user.server-providers.update', middleware: 'ability:write')]
    public function update(Request $request, ServerProvider $serverProvider): ServerProviderResource
    {
        $this->authorize('update', $serverProvider);

        // Ensure the server provider belongs to the authenticated user
        if ($serverProvider->user_id !== user()->id) {
            abort(404, 'Server provider not found');
        }

        $serverProvider = app(EditServerProvider::class)->edit($serverProvider, $request->all());

        return new ServerProviderResource($serverProvider);
    }

    #[Get('{serverProvider}/regions', name: 'api.user.server-providers.regions', middleware: 'ability:read')]
    public function regions(ServerProvider $serverProvider): JsonResponse
    {
        $this->authorize('view', $serverProvider);

        // Ensure the server provider belongs to the authenticated user
        if ($serverProvider->user_id !== user()->id) {
            abort(404, 'Server provider not found');
        }

        return response()->json($serverProvider->provider()->regions());
    }

    #[Get('{serverProvider}/regions/{region}/plans', name: 'api.user.server-providers.plans', middleware: 'ability:read')]
    public function plans(ServerProvider $serverProvider, string $region): JsonResponse
    {
        $this->authorize('view', $serverProvider);

        // Ensure the server provider belongs to the authenticated user
        if ($serverProvider->user_id !== user()->id) {
            abort(404, 'Server provider not found');
        }

        return response()->json($serverProvider->provider()->plans($region));
    }

    #[Delete('{serverProvider}', name: 'api.user.server-providers.delete', middleware: 'ability:write')]
    public function delete(ServerProvider $serverProvider): Response
    {
        $this->authorize('delete', $serverProvider);

        // Ensure the server provider belongs to the authenticated user
        if ($serverProvider->user_id !== user()->id) {
            abort(404, 'Server provider not found');
        }

        app(DeleteServerProvider::class)->delete($serverProvider);

        return response()->noContent();
    }
}
