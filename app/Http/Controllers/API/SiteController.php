<?php

namespace App\Http\Controllers\API;

use App\Actions\Site\CreateSite;
use App\Enums\SiteType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServerResource;
use App\Http\Resources\SiteResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
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

#[Prefix('api/projects/{project}/servers/{server}/sites')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'sites')]
class SiteController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all sites.')]
    #[ResponseFromApiResource(SiteResource::class, Site::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Site::class, $server]);

        $this->validateRoute($project, $server);

        return SiteResource::collection($server->sites()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.sites.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new site.')]
    #[BodyParam(name: 'type', required: true, enum: [SiteType::PHP, SiteType::PHP_BLANK, SiteType::PHPMYADMIN, SiteType::LARAVEL, SiteType::WORDPRESS])]
    #[BodyParam(name: 'domain', required: true)]
    #[BodyParam(name: 'aliases', type: 'array')]
    #[BodyParam(name: 'php_version', description: 'One of the installed PHP Versions', required: true, example: '7.4')]
    #[BodyParam(name: 'web_directory', description: 'Required for PHP and Laravel sites', example: 'public')]
    #[BodyParam(name: 'source_control', description: 'Source control ID, Required for Sites which support source control')]
    #[BodyParam(name: 'repository', description: 'Repository, Required for Sites which support source control', example: 'organization/repository')]
    #[BodyParam(name: 'branch', description: 'Branch, Required for Sites which support source control', example: 'main')]
    #[BodyParam(name: 'composer', type: 'boolean', description: 'Run composer if site supports composer', example: true)]
    #[BodyParam(name: 'version', description: 'Version, if the site type requires a version like PHPMyAdmin', example: '5.2.1')]
    #[ResponseFromApiResource(SiteResource::class, Site::class)]
    public function create(Request $request, Project $project, Server $server): SiteResource
    {
        $this->authorize('create', [Site::class, $server]);

        $this->validateRoute($project, $server);

        $this->validate($request, CreateSite::rules($server, $request->input()));

        $site = app(CreateSite::class)->create($server, $request->all());

        return new SiteResource($site);
    }

    #[Get('{site}', name: 'api.projects.servers.sites.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a site by ID.')]
    #[ResponseFromApiResource(SiteResource::class, Site::class)]
    public function show(Project $project, Server $server, Site $site): ServerResource
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        return new ServerResource($server);
    }

    #[Delete('{site}', name: 'api.projects.servers.sites.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete site.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, Site $site)
    {
        $this->authorize('delete', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        $site->delete();

        return response()->noContent();
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
