<?php

namespace App\Http\Controllers\API;

use App\Actions\SshKey\CreateSshKey;
use App\Actions\SshKey\DeleteKeyFromServer;
use App\Actions\SshKey\DeployKeyToServer;
use App\Http\Controllers\Controller;
use App\Http\Resources\SshKeyResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\SshKey;
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

#[Prefix('api/projects/{project}/servers/{server}/ssh-keys')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'ssh-keys')]
class ServerSSHKeyController extends Controller
{
    #[Get('/', name: 'api.projects.servers.ssh-keys', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all ssh keys.')]
    #[ResponseFromApiResource(SshKeyResource::class, SshKey::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAnyServer', [SshKey::class, $server]);

        $this->validateRoute($project, $server);

        return SshKeyResource::collection($server->sshKeys()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.ssh-keys.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Deploy ssh key to server.')]
    #[BodyParam(name: 'key_id', description: 'The ID of the key.')]
    #[BodyParam(name: 'name', description: 'Key name, required if key_id is not provided.')]
    #[BodyParam(name: 'public_key', description: 'Public Key, required if key_id is not provided.')]
    #[ResponseFromApiResource(SshKeyResource::class, SshKey::class)]
    public function create(Request $request, Project $project, Server $server): SshKeyResource
    {
        $this->authorize('create', [SshKey::class, $server]);

        $this->validateRoute($project, $server);

        $sshKey = null;
        if ($request->has('key_id')) {
            $this->validate($request, DeployKeyToServer::rules($request->user(), $server));

            $sshKey = $request->user()->sshKeys()->findOrFail($request->key_id);
        }

        if (! $sshKey) {
            $this->validate($request, CreateSshKey::rules());
            $sshKey = app(CreateSshKey::class)->create($request->user(), $request->all());
        }

        app(DeployKeyToServer::class)->deploy($server, ['key_id' => $sshKey->id]);

        return new SshKeyResource($sshKey);
    }

    #[Delete('{sshKey}', name: 'api.projects.servers.ssh-keys.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete ssh key from server.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, SshKey $sshKey)
    {
        $this->authorize('delete', [$sshKey, $server]);

        $this->validateRoute($project, $server);

        app(DeleteKeyFromServer::class)->delete($server, $sshKey);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }
    }
}
