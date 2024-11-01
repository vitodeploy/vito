<?php

namespace App\Http\Controllers\API;

use App\Actions\Database\CreateDatabase;
use App\Http\Controllers\Controller;
use App\Http\Resources\DatabaseResource;
use App\Models\Database;
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

#[Prefix('api/projects/{project}/servers/{server}/databases')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'databases')]
class DatabaseController extends Controller
{
    #[Get('/', name: 'api.projects.servers.databases', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all databases.')]
    #[ResponseFromApiResource(DatabaseResource::class, Database::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Database::class, $server]);

        $this->validateRoute($project, $server);

        return DatabaseResource::collection($server->databases()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.databases.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new database.')]
    #[BodyParam(name: 'name', required: true)]
    #[ResponseFromApiResource(DatabaseResource::class, Database::class)]
    public function create(Request $request, Project $project, Server $server): DatabaseResource
    {
        $this->authorize('create', [Database::class, $server]);

        $this->validateRoute($project, $server);

        $this->validate($request, CreateDatabase::rules($server, $request->input()));

        $database = app(CreateDatabase::class)->create($server, $request->all());

        return new DatabaseResource($database);
    }

    #[Get('{database}', name: 'api.projects.servers.databases.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a database by ID.')]
    #[ResponseFromApiResource(DatabaseResource::class, Database::class)]
    public function show(Project $project, Server $server, Database $database): DatabaseResource
    {
        $this->authorize('view', [$database, $server]);

        $this->validateRoute($project, $server, $database);

        return new DatabaseResource($database);
    }

    #[Delete('{database}', name: 'api.projects.servers.databases.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete database.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, Database $database)
    {
        $this->authorize('delete', [$database, $server]);

        $this->validateRoute($project, $server, $database);

        $database->delete();

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?Database $database = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($database && $database->server_id !== $server->id) {
            abort(404, 'Database not found in server');
        }
    }
}
