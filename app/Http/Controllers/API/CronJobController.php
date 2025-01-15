<?php

namespace App\Http\Controllers\API;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
use App\Http\Controllers\Controller;
use App\Http\Resources\CronJobResource;
use App\Models\CronJob;
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

#[Prefix('api/projects/{project}/servers/{server}/cron-jobs')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'cron-jobs')]
class CronJobController extends Controller
{
    #[Get('/', name: 'api.projects.servers.cron-jobs', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all cron jobs.')]
    #[ResponseFromApiResource(CronJobResource::class, CronJob::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [CronJob::class, $server]);

        $this->validateRoute($project, $server);

        return CronJobResource::collection($server->cronJobs()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.cron-jobs.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new cron job.')]
    #[BodyParam(name: 'command', required: true)]
    #[BodyParam(name: 'user', required: true, enum: ['root', 'vito'])]
    #[BodyParam(name: 'frequency', description: 'Frequency of the cron job.', required: true, example: '* * * * *')]
    #[ResponseFromApiResource(CronJobResource::class, CronJob::class)]
    public function create(Request $request, Project $project, Server $server): CronJobResource
    {
        $this->authorize('create', [CronJob::class, $server]);

        $this->validateRoute($project, $server);

        $this->validate($request, CreateCronJob::rules($request->all(), $server));

        $cronJob = app(CreateCronJob::class)->create($server, $request->all());

        return new CronJobResource($cronJob);
    }

    #[Get('{cronJob}', name: 'api.projects.servers.cron-jobs.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a cron job by ID.')]
    #[ResponseFromApiResource(CronJobResource::class, CronJob::class)]
    public function show(Project $project, Server $server, CronJob $cronJob): CronJobResource
    {
        $this->authorize('view', [$cronJob, $server]);

        $this->validateRoute($project, $server, $cronJob);

        return new CronJobResource($cronJob);
    }

    #[Delete('{cronJob}', name: 'api.projects.servers.cron-jobs.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete cron job.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, CronJob $cronJob)
    {
        $this->authorize('delete', [$cronJob, $server]);

        $this->validateRoute($project, $server, $cronJob);

        app(DeleteCronJob::class)->delete($server, $cronJob);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?CronJob $cronJob = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($cronJob && $cronJob->server_id !== $server->id) {
            abort(404, 'Firewall rule not found in server');
        }
    }
}
