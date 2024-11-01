<?php

namespace App\Http\Controllers\API;

use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\DatabaseUserResource;
use App\Models\DatabaseUser;
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

#[Prefix('api/projects/{project}/servers/{server}/database-users')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'database-users')]
class DatabaseUserController extends Controller
{
    #[Get('/', name: 'api.projects.servers.database-users', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all database users.')]
    #[ResponseFromApiResource(DatabaseUserResource::class, DatabaseUser::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [DatabaseUser::class, $server]);

        $this->validateRoute($project, $server);

        return DatabaseUserResource::collection($server->databaseUsers()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.database-users.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new database user.')]
    #[BodyParam(name: 'username', required: true)]
    #[BodyParam(name: 'password', required: true)]
    #[BodyParam(name: 'host', description: 'Host, if it is a remote user.', example: '%')]
    #[ResponseFromApiResource(DatabaseUserResource::class, DatabaseUser::class)]
    public function create(Request $request, Project $project, Server $server): DatabaseUserResource
    {
        $this->authorize('create', [DatabaseUser::class, $server]);

        $this->validateRoute($project, $server);

        $this->validate($request, CreateDatabaseUser::rules($server, $request->input()));

        $databaseUser = app(CreateDatabaseUser::class)->create($server, $request->all());

        return new DatabaseUserResource($databaseUser);
    }

    #[Get('{databaseUser}', name: 'api.projects.servers.database-users.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a database user by ID.')]
    #[ResponseFromApiResource(DatabaseUserResource::class, DatabaseUser::class)]
    public function show(Project $project, Server $server, DatabaseUser $databaseUser): DatabaseUserResource
    {
        $this->authorize('view', [$databaseUser, $server]);

        $this->validateRoute($project, $server, $databaseUser);

        return new DatabaseUserResource($databaseUser);
    }

    #[Post('{databaseUser}/link', name: 'api.projects.servers.database-users.link', middleware: 'ability:write')]
    #[Endpoint(title: 'link', description: 'Link to databases')]
    #[BodyParam(name: 'databases', description: 'Array of database names to link to the user.', required: true)]
    #[ResponseFromApiResource(DatabaseUserResource::class, DatabaseUser::class)]
    public function link(Request $request, Project $project, Server $server, DatabaseUser $databaseUser): DatabaseUserResource
    {
        $this->authorize('update', [$databaseUser, $server]);

        $this->validateRoute($project, $server, $databaseUser);

        $this->validate($request, LinkUser::rules($server, $request->all()));

        $databaseUser = app(LinkUser::class)->link($databaseUser, $request->all());

        return new DatabaseUserResource($databaseUser);
    }

    #[Delete('{databaseUser}', name: 'api.projects.servers.database-users.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete database user.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, DatabaseUser $databaseUser)
    {
        $this->authorize('delete', [$databaseUser, $server]);

        $this->validateRoute($project, $server, $databaseUser);

        $databaseUser->delete();

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?DatabaseUser $databaseUser = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($databaseUser && $databaseUser->server_id !== $server->id) {
            abort(404, 'Database user not found in server');
        }
    }
}
