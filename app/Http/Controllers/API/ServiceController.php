<?php

namespace App\Http\Controllers\API;

use App\Actions\Service\Manage;
use App\Actions\Service\Uninstall;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/services')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'services')]
class ServiceController extends Controller
{
    #[Get('/', name: 'api.projects.servers.services', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all services.')]
    #[ResponseFromApiResource(ServiceResource::class, Service::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Service::class, $server]);

        $this->validateRoute($project, $server);

        return ServiceResource::collection($server->services()->simplePaginate(25));
    }

    #[Get('{service}', name: 'api.projects.servers.services.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a service by ID.')]
    #[ResponseFromApiResource(ServiceResource::class, Service::class)]
    public function show(Project $project, Server $server, Service $service): ServiceResource
    {
        $this->authorize('view', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        return new ServiceResource($service);
    }

    #[Post('{service}/start', name: 'api.projects.servers.services.start', middleware: 'ability:write')]
    #[Endpoint(title: 'start', description: 'Start service.')]
    #[Response(status: 204)]
    public function start(Project $project, Server $server, Service $service): \Illuminate\Http\Response
    {
        $this->authorize('update', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        app(Manage::class)->start($service);

        return response()->noContent();
    }

    #[Post('{service}/stop', name: 'api.projects.servers.services.stop', middleware: 'ability:write')]
    #[Endpoint(title: 'stop', description: 'Stop service.')]
    #[Response(status: 204)]
    public function stop(Project $project, Server $server, Service $service): \Illuminate\Http\Response
    {
        $this->authorize('update', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        app(Manage::class)->stop($service);

        return response()->noContent();
    }

    #[Post('{service}/restart', name: 'api.projects.servers.services.restart', middleware: 'ability:write')]
    #[Endpoint(title: 'restart', description: 'Restart service.')]
    #[Response(status: 204)]
    public function restart(Project $project, Server $server, Service $service): \Illuminate\Http\Response
    {
        $this->authorize('update', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        app(Manage::class)->restart($service);

        return response()->noContent();
    }

    #[Post('{service}/enable', name: 'api.projects.servers.services.enable', middleware: 'ability:write')]
    #[Endpoint(title: 'enable', description: 'Enable service.')]
    #[Response(status: 204)]
    public function enable(Project $project, Server $server, Service $service): \Illuminate\Http\Response
    {
        $this->authorize('update', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        app(Manage::class)->enable($service);

        return response()->noContent();
    }

    #[Post('{service}/disable', name: 'api.projects.servers.services.disable', middleware: 'ability:write')]
    #[Endpoint(title: 'disable', description: 'Disable service.')]
    #[Response(status: 204)]
    public function disable(Project $project, Server $server, Service $service): \Illuminate\Http\Response
    {
        $this->authorize('update', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        app(Manage::class)->disable($service);

        return response()->noContent();
    }

    #[Delete('{service}', name: 'api.projects.servers.services.uninstall', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete service.')]
    #[Response(status: 204)]
    public function uninstall(Project $project, Server $server, Service $service): \Illuminate\Http\Response
    {
        $this->authorize('delete', [$service, $server]);

        $this->validateRoute($project, $server, $service);

        app(Uninstall::class)->uninstall($service);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?Service $service = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($service && $service->server_id !== $server->id) {
            abort(404, 'Service not found in server');
        }
    }
}
