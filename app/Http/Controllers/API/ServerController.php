<?php

namespace App\Http\Controllers\API;

use App\Actions\Server\CreateServer;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerResource;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Throwable;

#[Group(name: 'servers')]
class ServerController extends Controller
{
    #[Endpoint(title: 'list', description: 'Get all servers in a project.')]
    #[ResponseFromApiResource(ServerResource::class, Server::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [Server::class, $project]);

        return ServerResource::collection($project->servers()->simplePaginate(25));
    }

    #[Endpoint(title: 'show', description: 'Get a server by ID.')]
    #[ResponseFromApiResource(ServerResource::class, Server::class)]
    public function show(Project $project, Server $server): ServerResource
    {
        $this->authorize('view', $server);

        return new ServerResource($server);
    }

    /**
     * @throws Throwable
     */
    #[Endpoint(title: 'create', description: 'Create a new server.')]
    #[BodyParam(name: 'provider', description: 'The server provider type', required: true)]
    #[BodyParam(name: 'server_provider', description: 'If the provider is not custom, the ID of the server provider profile')]
    #[BodyParam(name: 'name', description: 'The name of the server.', required: true)]
    #[BodyParam(name: 'os', description: 'The os of the server', required: true)]
    #[ResponseFromApiResource(ServerResource::class, Server::class)]
    public function store(Request $request, Project $project): ServerResource
    {
        $this->authorize('create', [Server::class, $project]);

        $this->validate($request, CreateServer::rules($project, $request->input()));

        $server = app(CreateServer::class)->create(auth()->user(), auth()->user()->currentProject, $request->all());

        return new ServerResource($server);
    }
}
