<?php

namespace App\Http\Controllers\API;

use App\Actions\Application\CreateApplication;
use App\Actions\Application\DeleteApplication;
use App\Actions\Application\UpdateApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/servers/{server}/applications')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class ApplicationController extends Controller
{
    #[Get('/', name: 'api.projects.servers.applications', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Application::class, $server]);

        $this->validateRoute($project, $server);

        return ApplicationResource::collection($server->applications()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.applications.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Server $server): ApplicationResource
    {
        $this->authorize('create', [Application::class, $server]);

        $this->validateRoute($project, $server);

        $application = app(CreateApplication::class)->create($server, $request->input());

        return new ApplicationResource($application);
    }

    #[Get('{application}', name: 'api.projects.servers.applications.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, Application $application): ApplicationResource
    {
        $this->authorize('view', [$application, $server]);

        $this->validateRoute($project, $server, $application);

        return new ApplicationResource($application);
    }

    #[Put('{application}', name: 'api.projects.servers.applications.update', middleware: 'ability:write')]
    public function update(Request $request, Project $project, Server $server, Application $application): ApplicationResource
    {
        $this->authorize('update', [$application, $server]);

        $this->validateRoute($project, $server, $application);

        app(UpdateApplication::class)->update($application, $request->input());

        return new ApplicationResource($application->refresh());
    }

    #[Delete('{application}', name: 'api.projects.servers.applications.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, Application $application): \Illuminate\Http\Response
    {
        $this->authorize('delete', [$application, $server]);

        $this->validateRoute($project, $server, $application);

        app(DeleteApplication::class)->delete($application);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?Application $application = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($application && $application->server_id !== $server->id) {
            abort(404, 'Application not found in server');
        }
    }
}
