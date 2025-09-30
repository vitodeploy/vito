<?php

namespace App\Http\Controllers\API;

use App\Actions\Site\Deploy;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeploymentResource;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}/deployments')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class DeploymentController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites.deployments', middleware: 'ability:read')]
    public function index(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        $deployments = $site->deployments()
            ->with(['log'])
            ->latest()
            ->simplePaginate(25);

        return DeploymentResource::collection($deployments);
    }

    #[Post('/', name: 'api.projects.servers.sites.deployments.store', middleware: 'ability:write')]
    public function store(Project $project, Server $server, Site $site): DeploymentResource
    {
        $this->authorize('update', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        try {
            $deployment = app(Deploy::class)->run($site);

            return new DeploymentResource($deployment);
        } catch (DeploymentScriptIsEmptyException) {
            abort(422, 'Deployment script is empty');
        }
    }

    #[Get('{deployment}', name: 'api.projects.servers.sites.deployments.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, Site $site, Deployment $deployment): DeploymentResource
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        if ($deployment->site_id !== $site->id) {
            abort(404, 'Deployment not found for this site');
        }

        return new DeploymentResource($deployment);
    }

    private function validateRoute(Project $project, Server $server, ?Site $site = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($site && $site->server_id !== $server->id) {
            abort(404, 'Site not found in server');
        }
    }
}
