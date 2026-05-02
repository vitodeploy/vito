<?php

namespace App\Http\Controllers\API;

use App\Actions\Server\CreateServer;
use App\Actions\Server\RebootServer;
use App\Actions\Server\Update;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerResource;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class ServerController extends Controller
{
    #[Get('/', name: 'api.projects.servers', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [Server::class, $project]);

        return ServerResource::collection($project->servers()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project): ServerResource
    {
        $this->authorize('create', [Server::class, $project]);

        $user = user();
        $server = app(CreateServer::class)->create($user, $project, $request->all());

        return new ServerResource($server);
    }

    #[Get('{server}', name: 'api.projects.servers.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server): ServerResource
    {
        $this->authorize('view', [$server, $project]);

        $this->validateRoute($project, $server);

        return new ServerResource($server);
    }

    #[Post('{server}/reboot', name: 'api.projects.servers.reboot', middleware: 'ability:write')]
    public function reboot(Project $project, Server $server): Response
    {
        $this->authorize('update', [$server, $project]);

        $this->validateRoute($project, $server);

        app(RebootServer::class)->reboot($server);

        return response()->noContent();
    }

    #[Post('{server}/upgrade', name: 'api.projects.servers.upgrade', middleware: 'ability:write')]
    public function upgrade(Project $project, Server $server): Response
    {
        $this->authorize('update', [$server, $project]);

        $this->validateRoute($project, $server);

        app(Update::class)->update($server);

        return response()->noContent();
    }

    #[Delete('{server}', name: 'api.projects.servers.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server): Response
    {
        $this->authorize('delete', [$server, $project]);

        $this->validateRoute($project, $server);

        $server->delete();

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }
    }
}
