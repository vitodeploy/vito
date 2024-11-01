<?php

namespace App\Http\Controllers\API;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Actions\ServerProvider\EditServerProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerProviderResource;
use App\Models\Project;
use App\Models\ServerProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/server-providers')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'server-providers')]
class ServerProviderController extends Controller
{
    #[Get('/', name: 'api.projects.server-providers', middleware: 'ability:read')]
    #[Endpoint(title: 'list')]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', ServerProvider::class);

        $serverProviders = ServerProvider::getByProjectId($project->id)->simplePaginate(25);

        return ServerProviderResource::collection($serverProviders);
    }

    #[Post('/', name: 'api.projects.server-providers.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create')]
    #[BodyParam(name: 'provider', description: 'The provider (aws, linode, hetzner, digitalocean, vultr, ...)', required: true)]
    #[BodyParam(name: 'name', description: 'The name of the server provider.', required: true)]
    #[BodyParam(name: 'token', description: 'The token if provider requires api token')]
    #[BodyParam(name: 'key', description: 'The key if provider requires key')]
    #[BodyParam(name: 'secret', description: 'The secret if provider requires key')]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class)]
    public function create(Request $request, Project $project): ServerProviderResource
    {
        $this->authorize('create', ServerProvider::class);

        $this->validate($request, CreateServerProvider::rules($request->all()));

        $serverProvider = app(CreateServerProvider::class)->create(auth()->user(), $project, $request->all());

        return new ServerProviderResource($serverProvider);
    }

    #[Get('{serverProvider}', name: 'api.projects.server-providers.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show')]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class)]
    public function show(Project $project, ServerProvider $serverProvider)
    {
        $this->authorize('view', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        return new ServerProviderResource($serverProvider);
    }

    #[Put('{serverProvider}', name: 'api.projects.server-providers.update', middleware: 'ability:write')]
    #[Endpoint(title: 'update')]
    #[BodyParam(name: 'name', description: 'The name of the server provider.', required: true)]
    #[BodyParam(name: 'global', description: 'Accessible in all projects', enum: [true, false])]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class)]
    public function update(Request $request, Project $project, ServerProvider $serverProvider)
    {
        $this->authorize('update', $serverProvider);

        $this->validateRoute($project, $serverProvider);

        $this->validate($request, EditServerProvider::rules());

        $serverProvider = app(EditServerProvider::class)->edit($serverProvider, $project, $request->all());

        return new ServerProviderResource($serverProvider);
    }

    #[Delete('{serverProvider}', name: 'api.projects.server-providers.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete')]
    #[Response(status: 204)]
    public function delete(Project $project, ServerProvider $serverProvider)
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
