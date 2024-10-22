<?php

namespace App\Http\Controllers\API;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerProviderResource;
use App\Models\Project;
use App\Models\ServerProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group(name: 'server-providers')]
class ServerProviderController extends Controller
{
    #[Endpoint(title: 'list')]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', ServerProvider::class);

        $serverProviders = ServerProvider::getByProjectId($project->id)->simplePaginate(25);

        return ServerProviderResource::collection($serverProviders);
    }

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

    #[Endpoint(title: 'show')]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class)]
    public function show(Project $project, ServerProvider $serverProvider)
    {
        $this->authorize('view', $serverProvider);

        return new ServerProviderResource($serverProvider);
    }

    #[Endpoint(title: 'update')]
    #[ResponseFromApiResource(ServerProviderResource::class, ServerProvider::class)]
    public function update(Request $request, Project $project, ServerProvider $serverProvider) {}

    /**
     * @throws Exception
     */
    #[Endpoint(title: 'delete')]
    #[Response(status: 204)]
    public function delete(Project $project, ServerProvider $serverProvider)
    {
        $this->authorize('delete', $serverProvider);

        app(DeleteServerProvider::class)->delete($serverProvider);

        return response()->noContent();
    }
}
