<?php

namespace App\Http\Controllers\API;

use App\Actions\Server\CreateServer;
use App\Actions\Server\RebootServer;
use App\Actions\Server\Update;
use App\Enums\Database;
use App\Enums\PHP;
use App\Enums\ServerProvider;
use App\Enums\Webserver;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerResource;
use App\Models\Project;
use App\Models\Server;
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

#[Prefix('api/projects/{project}/servers')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'servers')]
class ServerController extends Controller
{
    #[Get('/', name: 'api.projects.servers', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all servers in a project.')]
    #[ResponseFromApiResource(ServerResource::class, Server::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [Server::class, $project]);

        return ServerResource::collection($project->servers()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new server.')]
    #[BodyParam(name: 'provider', description: 'The server provider type', required: true)]
    #[BodyParam(name: 'server_provider', description: 'If the provider is not custom, the ID of the server provider profile', enum: [ServerProvider::CUSTOM, ServerProvider::HETZNER, ServerProvider::DIGITALOCEAN, ServerProvider::LINODE, ServerProvider::VULTR])]
    #[BodyParam(name: 'region', description: 'Provider region if the provider is not custom')]
    #[BodyParam(name: 'plan', description: 'Provider plan if the provider is not custom')]
    #[BodyParam(name: 'ip', description: 'SSH IP address if the provider is custom')]
    #[BodyParam(name: 'port', description: 'SSH Port if the provider is custom')]
    #[BodyParam(name: 'name', description: 'The name of the server.', required: true)]
    #[BodyParam(name: 'os', description: 'The os of the server', required: true)]
    #[BodyParam(name: 'webserver', description: 'Web server', required: true, enum: [Webserver::NONE, Webserver::NGINX])]
    #[BodyParam(name: 'database', description: 'Database', required: true, enum: [Database::NONE, Database::MYSQL57, Database::MYSQL80, Database::MARIADB103, Database::MARIADB104, Database::MARIADB103, Database::POSTGRESQL12, Database::POSTGRESQL13, Database::POSTGRESQL14, Database::POSTGRESQL15, Database::POSTGRESQL16], )]
    #[BodyParam(name: 'php', description: 'PHP version', required: true, enum: [PHP::V70, PHP::V71, PHP::V72, PHP::V73, PHP::V74, PHP::V80, PHP::V81, PHP::V82, PHP::V83])]
    #[ResponseFromApiResource(ServerResource::class, Server::class)]
    public function create(Request $request, Project $project): ServerResource
    {
        $this->authorize('create', [Server::class, $project]);

        $this->validate($request, CreateServer::rules($project, $request->input()));

        $server = app(CreateServer::class)->create(auth()->user(), $project, $request->all());

        return new ServerResource($server);
    }

    #[Get('{server}', name: 'api.projects.servers.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a server by ID.')]
    #[ResponseFromApiResource(ServerResource::class, Server::class)]
    public function show(Project $project, Server $server): ServerResource
    {
        $this->authorize('view', [$server, $project]);

        $this->validateRoute($project, $server);

        return new ServerResource($server);
    }

    #[Post('{server}/reboot', name: 'api.projects.servers.reboot', middleware: 'ability:write')]
    #[Endpoint(title: 'reboot', description: 'Reboot a server.')]
    #[Response(status: 204)]
    public function reboot(Project $project, Server $server)
    {
        $this->authorize('update', [$server, $project]);

        $this->validateRoute($project, $server);

        app(RebootServer::class)->reboot($server);

        return response()->noContent();
    }

    #[Post('{server}/upgrade', name: 'api.projects.servers.upgrade', middleware: 'ability:write')]
    #[Endpoint(title: 'upgrade', description: 'Upgrade server.')]
    #[Response(status: 204)]
    public function upgrade(Project $project, Server $server)
    {
        $this->authorize('update', [$server, $project]);

        $this->validateRoute($project, $server);

        app(Update::class)->update($server);

        return response()->noContent();
    }

    #[Delete('{server}', name: 'api.projects.servers.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete server.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server)
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
