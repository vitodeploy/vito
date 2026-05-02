<?php

namespace App\Http\Controllers\API;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\DeleteDatabase;
use App\Http\Controllers\Controller;
use App\Http\Resources\DatabaseResource;
use App\Models\Database;
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

#[Prefix('api/projects/{project}/servers/{server}/databases')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class DatabaseController extends Controller
{
    #[Get('/', name: 'api.projects.servers.databases', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Database::class, $server]);

        $this->validateRoute($project, $server);

        return DatabaseResource::collection($server->databases()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.databases.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Server $server): DatabaseResource
    {
        $this->authorize('create', [Database::class, $server]);

        $this->validateRoute($project, $server);

        $database = app(CreateDatabase::class)->create($server, $request->all());

        return new DatabaseResource($database);
    }

    #[Get('{database}', name: 'api.projects.servers.databases.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, Database $database): DatabaseResource
    {
        $this->authorize('view', [$database, $server]);

        $this->validateRoute($project, $server, $database);

        return new DatabaseResource($database);
    }

    #[Delete('{database}', name: 'api.projects.servers.databases.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, Database $database): Response
    {
        $this->authorize('delete', [$database, $server]);

        $this->validateRoute($project, $server, $database);

        app(DeleteDatabase::class)->delete($server, $database);

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
