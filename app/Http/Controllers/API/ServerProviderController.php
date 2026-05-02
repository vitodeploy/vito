<?php

namespace App\Http\Controllers\API;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Actions\ServerProvider\EditServerProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerProviderResource;
use App\Models\Project;
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

/**
 * @deprecated Use UserServerProviderController instead. This controller will be removed in a future version.
 */
#[Prefix('api/projects/{project}/server-providers')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class ServerProviderController extends Controller
{
    /**
     * @deprecated Use GET /api/server-providers instead
     */
    #[Get('/', name: 'api.projects.server-providers', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', ServerProvider::class);

        $serverProviders = ServerProvider::getByProjectId($project->id, user())->simplePaginate(25);

        return ServerProviderResource::collection($serverProviders);
    }

    /**
     * @deprecated Use POST /api/server-providers instead
     */
    #[Post('/', name: 'api.projects.server-providers.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project): ServerProviderResource
    {
        $this->authorize('create', ServerProvider::class);

        $user = user();
        $serverProvider = app(CreateServerProvider::class)->create($user, $request->all());

        return new ServerProviderResource($serverProvider);
    }

    /**
     * @deprecated Use GET /api/server-providers/{serverProvider} instead
     */
    #[Get('{serverProvider}', name: 'api.projects.server-providers.show', middleware: 'ability:read')]
    public function show(Project $project, ServerProvider $serverProvider): ServerProviderResource
    {
        $this->authorize('view', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        return new ServerProviderResource($serverProvider);
    }

    /**
     * @deprecated Use PUT /api/server-providers/{serverProvider} instead
     */
    #[Put('{serverProvider}', name: 'api.projects.server-providers.update', middleware: 'ability:write')]
    public function update(Request $request, Project $project, ServerProvider $serverProvider): ServerProviderResource
    {
        $this->authorize('update', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        $serverProvider = app(EditServerProvider::class)->edit($serverProvider, $request->all());

        return new ServerProviderResource($serverProvider);
    }

    /**
     * @deprecated Use GET /api/server-providers/{serverProvider}/regions instead
     */
    #[Get('{serverProvider}/regions', name: 'api.projects.server-providers.regions', middleware: 'ability:read')]
    public function regions(Project $project, ServerProvider $serverProvider): JsonResponse
    {
        $this->authorize('view', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        return response()->json($serverProvider->provider()->regions());
    }

    /**
     * @deprecated Use GET /api/server-providers/{serverProvider}/regions/{region}/plans instead
     */
    #[Get('{serverProvider}/regions/{region}/plans', name: 'api.projects.server-providers.plans', middleware: 'ability:read')]
    public function plans(Project $project, ServerProvider $serverProvider, string $region): JsonResponse
    {
        $this->authorize('view', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        return response()->json($serverProvider->provider()->plans($region));
    }

    /**
     * @deprecated Use DELETE /api/server-providers/{serverProvider} instead
     */
    #[Delete('{serverProvider}', name: 'api.projects.server-providers.delete', middleware: 'ability:write')]
    public function delete(Project $project, ServerProvider $serverProvider): Response
    {
        $this->authorize('delete', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        app(DeleteServerProvider::class)->delete($serverProvider);

        return response()->noContent();
    }

    private function validateRoute(Project $project, ServerProvider $serverProvider): void
    {
        if (! $serverProvider->project_id) {
            return;
        }

        if ($project->id !== $serverProvider->project_id) {
            abort(404, 'Server provider not found in project');
        }
    }
}
