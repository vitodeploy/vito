<?php

namespace App\Http\Controllers\API;

use App\Actions\Site\CreateSite;
use App\Actions\Site\Deploy;
use App\Actions\Site\UpdateAliases;
use App\Actions\Site\UpdateDeploymentScript;
use App\Actions\Site\UpdateEnv;
use App\Actions\Site\UpdateLoadBalancer;
use App\Actions\Site\UpdateWebDirectory;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeploymentResource;
use App\Http\Resources\SiteResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/servers/{server}/sites')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class SiteController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Site::class, $server]);

        $this->validateRoute($project, $server);

        return SiteResource::collection($server->sites()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.sites.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Server $server): SiteResource
    {
        $this->authorize('create', [Site::class, $server]);

        $this->validateRoute($project, $server);

        $site = app(CreateSite::class)->create($server, $request->all());

        return new SiteResource($site);
    }

    #[Get('{site}', name: 'api.projects.servers.sites.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, Site $site): SiteResource
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        return new SiteResource($site);
    }

    #[Delete('{site}', name: 'api.projects.servers.sites.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, Site $site): Response
    {
        $this->authorize('delete', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        $site->delete();

        return response()->noContent();
    }

    #[Post('{site}/load-balancer', name: 'api.projects.servers.sites.load-balancer', middleware: 'ability:write')]
    public function updateLoadBalancer(Request $request, Project $project, Server $server, Site $site): SiteResource
    {
        $this->authorize('update', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        app(UpdateLoadBalancer::class)->update($site, $request->all());

        return new SiteResource($site);
    }

    #[Put('{site}/aliases', name: 'api.projects.servers.sites.aliases', middleware: 'ability:write')]
    public function updateAliases(Request $request, Project $project, Server $server, Site $site): SiteResource
    {
        $this->authorize('update', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        app(UpdateAliases::class)->update($site, $request->all());

        return new SiteResource($site);
    }

    #[Put('{site}/web-directory', name: 'api.projects.servers.sites.web-directory', middleware: 'ability:write')]
    public function updateWebDirectory(Request $request, Project $project, Server $server, Site $site): SiteResource
    {
        $this->authorize('update', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        app(UpdateWebDirectory::class)->update($site, $request->all());

        return new SiteResource($site);
    }

    #[Post('{site}/deploy', name: 'api.projects.servers.sites.deploy', middleware: 'ability:write')]
    public function deploy(Request $request, Project $project, Server $server, Site $site): DeploymentResource
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

    #[Put('{site}/deployment-script', name: 'api.projects.servers.sites.deployment-script', middleware: 'ability:write')]
    public function updateDeploymentScript(Request $request, Project $project, Server $server, Site $site): Response
    {
        $this->authorize('update', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        app(UpdateDeploymentScript::class)->update($site->deploymentScript, $request->all());

        return response()->noContent();
    }

    #[Get('{site}/deployment-script', name: 'api.projects.servers.sites.deployment-script.show', middleware: 'ability:read')]
    public function showDeploymentScript(Project $project, Server $server, Site $site): JsonResponse
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        return response()->json([
            'script' => $site->deploymentScript?->content,
        ]);
    }

    #[Get('{site}/env', name: 'api.projects.servers.sites.env.show', middleware: 'ability:read')]
    public function showEnv(Project $project, Server $server, Site $site): JsonResponse
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        return response()->json([
            'data' => [
                'env' => $site->getEnv(),
            ],
        ]);
    }

    #[Put('{site}/env', name: 'api.projects.servers.sites.env', middleware: 'ability:write')]
    public function updateEnv(Request $request, Project $project, Server $server, Site $site): SiteResource
    {
        $this->authorize('update', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        app(UpdateEnv::class)->update($site, $request->all());

        return new SiteResource($site);
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
