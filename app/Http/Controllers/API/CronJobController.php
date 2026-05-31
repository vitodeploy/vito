<?php

namespace App\Http\Controllers\API;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
use App\Exceptions\SSHError;
use App\Http\Controllers\Controller;
use App\Http\Resources\CronJobResource;
use App\Models\CronJob;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/cron-jobs')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class CronJobController extends Controller
{
    #[Get('/', name: 'api.projects.servers.cron-jobs', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [CronJob::class, $server]);

        $this->validateRoute($project, $server);

        return CronJobResource::collection($server->cronJobs()->simplePaginate(25));
    }

    /**
     * @throws SSHError
     */
    #[Post('/', name: 'api.projects.servers.cron-jobs.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Server $server): CronJobResource
    {
        $this->authorize('create', [CronJob::class, $server]);

        $this->validateRoute($project, $server);

        $cronJob = app(CreateCronJob::class)->create($server, $request->all());

        return new CronJobResource($cronJob);
    }

    #[Get('{cronJob}', name: 'api.projects.servers.cron-jobs.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, CronJob $cronJob): CronJobResource
    {
        $this->authorize('view', [$cronJob, $server]);

        $this->validateRoute($project, $server, $cronJob);

        return new CronJobResource($cronJob);
    }

    /**
     * @throws SSHError
     */
    #[Delete('{cronJob}', name: 'api.projects.servers.cron-jobs.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, CronJob $cronJob): Response
    {
        $this->authorize('delete', [$cronJob, $server]);

        $this->validateRoute($project, $server, $cronJob);

        app(DeleteCronJob::class)->delete($server, $cronJob);

        return response()->noContent();
    }

    #[Get('/sites/{site}/cron-jobs', name: 'api.projects.servers.sites.cron-jobs', middleware: 'ability:read')]
    public function siteIndex(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('viewAny', [CronJob::class, $server, $site]);

        $this->validateRoute($project, $server, site: $site);

        return CronJobResource::collection($site->cronJobs()->simplePaginate(25));
    }

    /**
     * @throws SSHError
     */
    #[Post('/sites/{site}/cron-jobs', name: 'api.projects.servers.sites.cron-jobs.create', middleware: 'ability:write')]
    public function siteCreate(Request $request, Project $project, Server $server, Site $site): CronJobResource
    {
        $this->authorize('create', [CronJob::class, $server, $site]);

        $this->validateRoute($project, $server, site: $site);

        $cronJob = app(CreateCronJob::class)->create($server, $request->all(), $site);

        return new CronJobResource($cronJob);
    }

    #[Get('/sites/{site}/cron-jobs/{cronJob}', name: 'api.projects.servers.sites.cron-jobs.show', middleware: 'ability:read')]
    public function siteShow(Project $project, Server $server, Site $site, CronJob $cronJob): CronJobResource
    {
        $this->authorize('view', [$cronJob, $server, $site]);

        $this->validateRoute($project, $server, $cronJob, $site);

        return new CronJobResource($cronJob);
    }

    /**
     * @throws SSHError
     */
    #[Delete('/sites/{site}/cron-jobs/{cronJob}', name: 'api.projects.servers.sites.cron-jobs.delete', middleware: 'ability:write')]
    public function siteDelete(Project $project, Server $server, Site $site, CronJob $cronJob): Response
    {
        $this->authorize('delete', [$cronJob, $server, $site]);

        $this->validateRoute($project, $server, $cronJob, $site);

        app(DeleteCronJob::class)->delete($server, $cronJob);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?CronJob $cronJob = null, ?Site $site = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($site && $site->server_id !== $server->id) {
            abort(404, 'Site not found in server');
        }

        if ($cronJob && $cronJob->server_id !== $server->id) {
            abort(404, 'Cron job does not belong to the specified server');
        }

        if ($site && $cronJob && $cronJob->site_id !== $site->id) {
            abort(404, 'Cron job not found in site');
        }
    }
}
