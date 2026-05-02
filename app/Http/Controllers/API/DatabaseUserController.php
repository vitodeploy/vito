<?php

namespace App\Http\Controllers\API;

use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\DeleteDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\DatabaseUserResource;
use App\Models\DatabaseUser;
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

#[Prefix('api/projects/{project}/servers/{server}/database-users')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class DatabaseUserController extends Controller
{
    #[Get('/', name: 'api.projects.servers.database-users', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [DatabaseUser::class, $server]);

        $this->validateRoute($project, $server);

        return DatabaseUserResource::collection($server->databaseUsers()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.database-users.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Server $server): DatabaseUserResource
    {
        $this->authorize('create', [DatabaseUser::class, $server]);

        $this->validateRoute($project, $server);

        $databaseUser = app(CreateDatabaseUser::class)->create($server, $request->all());

        return new DatabaseUserResource($databaseUser);
    }

    #[Get('{databaseUser}', name: 'api.projects.servers.database-users.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, DatabaseUser $databaseUser): DatabaseUserResource
    {
        $this->authorize('view', [$databaseUser, $server]);

        $this->validateRoute($project, $server, $databaseUser);

        return new DatabaseUserResource($databaseUser);
    }

    #[Post('{databaseUser}/link', name: 'api.projects.servers.database-users.link', middleware: 'ability:write')]
    public function link(Request $request, Project $project, Server $server, DatabaseUser $databaseUser): DatabaseUserResource
    {
        $this->authorize('update', [$databaseUser, $server]);

        $this->validateRoute($project, $server, $databaseUser);

        $databaseUser = app(LinkUser::class)->link($databaseUser, $request->all());

        return new DatabaseUserResource($databaseUser);
    }

    #[Delete('{databaseUser}', name: 'api.projects.servers.database-users.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, DatabaseUser $databaseUser): Response
    {
        $this->authorize('delete', [$databaseUser, $server]);

        $this->validateRoute($project, $server, $databaseUser);

        app(DeleteDatabaseUser::class)->delete($server, $databaseUser);

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
