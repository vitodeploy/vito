<?php

namespace App\Http\Controllers\API;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\DeleteWorker;
use App\Actions\Worker\EditWorker;
use App\Actions\Worker\GetWorkerLogs;
use App\Actions\Worker\ManageWorker;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkerResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
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
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/servers/{server}')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'workers')]
class WorkerController extends Controller
{
    #[Get('/workers', name: 'api.projects.servers.workers', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all workers.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class, collection: true, paginate: 25)]
    public function serverIndex(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('view', [$project, $server]);

        $this->validateRoute($project, $server);

        $workers = $server->workers()
            ->latest()
            ->simplePaginate(25);

        return WorkerResource::collection($workers);
    }

    #[Get('/sites/{site}/workers', name: 'api.projects.servers.sites.workers', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all site workers.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class, collection: true, paginate: 25)]
    public function siteIndex(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('view', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $workers = $site->workers()
            ->latest()
            ->simplePaginate(25);

        return WorkerResource::collection($workers);
    }

    #[Get('/workers/{worker}', name: 'api.projects.servers.workers.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a server worker by ID.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function serverShow(Project $project, Server $server, Worker $worker): WorkerResource
    {
        $this->authorize('view', [$project, $server, $worker]);

        $this->validateRoute($project, $server, worker: $worker);

        return new WorkerResource($worker);
    }

    #[Get('/sites/{site}/workers/{worker}', name: 'api.projects.servers.sites.workers.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a server worker by ID.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function siteShow(Project $project, Server $server, Site $site, Worker $worker): WorkerResource
    {
        $this->authorize('view', [$project, $server, $site, $worker]);

        $this->validateRoute($project, $server, $site, $worker);

        return new WorkerResource($worker);
    }

    #[Post('/workers/{site?}', name: 'api.projects.servers.workers.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new worker.')]
    #[BodyParam(name: 'name', required: true)]
    #[BodyParam(name: 'command', required: true)]
    #[BodyParam(name: 'user', required: true)]
    #[BodyParam(name: 'auto_start', type: 'boolean', required: true)]
    #[BodyParam(name: 'auto_restart', type: 'boolean', required: true)]
    #[BodyParam(name: 'numprocs', required: true)]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function create(Request $request, Project $project, Server $server, ?Site $site = null): WorkerResource
    {
        $this->authorize('create', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $worker = app(CreateWorker::class)->create($server, $request->all(), $site);

        return new WorkerResource($worker);
    }

    #[Put('/workers/{worker}/{site?}', name: 'api.projects.servers.workers.update', middleware: 'ability:write')]
    #[Endpoint(title: 'update', description: 'Update a worker.')]
    #[BodyParam(name: 'name', required: true)]
    #[BodyParam(name: 'command', required: true)]
    #[BodyParam(name: 'user', required: true)]
    #[BodyParam(name: 'auto_start', type: 'boolean', required: true)]
    #[BodyParam(name: 'auto_restart', type: 'boolean', required: true)]
    #[BodyParam(name: 'numprocs', required: true)]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function update(Request $request, Project $project, Server $server, Worker $worker, ?Site $site = null): WorkerResource
    {
        $this->authorize('update', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site, $worker);

        $worker = app(EditWorker::class)->edit($worker, $request->all());

        return new WorkerResource($worker);
    }

    #[Post('/workers/{worker}/start', name: 'api.projects.servers.workers.start', middleware: 'ability:write')]
    #[Endpoint(title: 'update', description: 'Start a worker.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function start(Request $request, Project $project, Server $server, Worker $worker): WorkerResource
    {
        $this->authorize('update', [$project, $server]);

        $this->validateRoute($project, $server, worker: $worker);

        app(ManageWorker::class)->start($worker);

        return new WorkerResource($worker);
    }

    #[Post('/workers/{worker}/restart', name: 'api.projects.servers.workers.restart', middleware: 'ability:write')]
    #[Endpoint(title: 'update', description: 'Restart a worker.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function restart(Request $request, Project $project, Server $server, Worker $worker): WorkerResource
    {
        $this->authorize('update', [$project, $server]);

        $this->validateRoute($project, $server, worker: $worker);

        app(ManageWorker::class)->restart($worker);

        return new WorkerResource($worker);
    }

    #[Get('/workers/{worker}/logs', name: 'api.projects.servers.workers.logs', middleware: 'ability:read')]
    #[Endpoint(title: 'logs', description: 'Get worker logs.')]
    #[ResponseFromApiResource(WorkerResource::class, Worker::class)]
    public function logs(Project $project, Server $server, Worker $worker): JsonResponse
    {
        $this->authorize('view', [$project, $server, $worker]);

        $this->validateRoute($project, $server, worker: $worker);

        $logs = app(GetWorkerLogs::class)->getLogs($worker);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    #[Delete('/workers/{worker}/{site?}', name: 'api.projects.servers.workers.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete worker.')]
    #[Response(status: 204)]
    public function delete(Request $request, Project $project, Server $server, Worker $worker, ?Site $site = null): \Illuminate\Http\Response
    {
        $this->authorize('delete', [$project, $server, $site, $worker]);

        $this->validateRoute($project, $server, $site, $worker);

        app(DeleteWorker::class)->delete($worker);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?Site $site = null, ?Worker $worker = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($site && $site->server_id !== $server->id) {
            abort(404, 'Site not found in server');
        }

        if ($site && $worker && $worker->site_id !== $site->id) {
            abort(404, 'Worker not found in site');
        }

        if ($worker && $worker->server_id !== $server->id) {
            abort(404, 'Worker not found in server');
        }
    }
}
