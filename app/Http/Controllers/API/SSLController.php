<?php

namespace App\Http\Controllers\API;

use App\Actions\SSL\ActivateSSL;
use App\Actions\SSL\CreateSSL;
use App\Actions\SSL\DeactivateSSL;
use App\Actions\SSL\DeleteSSL;
use App\Enums\SslType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SslResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
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

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}/ssls')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'ssls')]
class SSLController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites.ssls', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all SSL certificates.')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('view', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $ssls = $site->ssls()
            ->with(['log'])
            ->latest()
            ->simplePaginate(25);

        return SslResource::collection($ssls);
    }

    #[Get('{ssl}', name: 'api.projects.servers.sites.ssls.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get an SSL certificate by ID.')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class)]
    public function show(Project $project, Server $server, Site $site, Ssl $ssl): SslResource
    {
        $this->authorize('view', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        return new SslResource($ssl);
    }

    #[Post('/letsencrypt', name: 'api.projects.servers.sites.ssls.create-letsencrypt', middleware: 'ability:write')]
    #[Endpoint(title: 'create-letsencrypt', description: 'Create a new Let\'s Encrypt SSL certificate.')]
    #[BodyParam(name: 'email', required: true)]
    #[BodyParam(name: 'aliases', type: 'boolean', description: 'Set SSL for site\'s aliases as well')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class)]
    public function createLetsEncrypt(Request $request, Project $project, Server $server, Site $site): SslResource
    {
        $this->authorize('create', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $ssl = app(CreateSSL::class)->create($site, array_merge($request->all(), ['type' => SslType::LETSENCRYPT]));

        return new SslResource($ssl);
    }

    #[Post('/custom', name: 'api.projects.servers.sites.ssls.create-custom', middleware: 'ability:write')]
    #[Endpoint(title: 'create-letsencrypt', description: 'Create a custom SSL certificate.')]
    #[BodyParam(name: 'private', required: true)]
    #[BodyParam(name: 'certificate', required: true)]
    #[BodyParam(name: 'expires_at', type: 'data', required: true)]
    #[BodyParam(name: 'aliases', type: 'boolean', description: 'Set SSL for site\'s aliases as well')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class)]
    public function createCustom(Request $request, Project $project, Server $server, Site $site): SslResource
    {
        $this->authorize('create', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $ssl = app(CreateSSL::class)
            ->create($site, array_merge($request->all(), ['type' => SslType::CUSTOM]));

        return new SslResource($ssl);
    }

    #[Post('/{ssl}/activate', name: 'api.projects.servers.sites.ssls.activate', middleware: 'ability:write')]
    #[Endpoint(title: 'activate', description: 'Activate SSL certificate.')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class)]
    public function activate(Request $request, Project $project, Server $server, Site $site, Ssl $ssl): SslResource
    {
        $this->authorize('update', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        app(ActivateSSL::class)->activate($ssl);

        return new SslResource($ssl);
    }

    #[Post('/{ssl}/deactivate', name: 'api.projects.servers.sites.ssls.deactivate', middleware: 'ability:write')]
    #[Endpoint(title: 'activate', description: 'Deactivate SSL certificate.')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class)]
    public function deactivate(Request $request, Project $project, Server $server, Site $site, Ssl $ssl): SslResource
    {
        $this->authorize('update', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        app(DeactivateSSL::class)->deactivate($ssl);

        return new SslResource($ssl);
    }

    #[Delete('/{ssl}', name: 'api.projects.servers.sites.ssls.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete SSL certificate.')]
    #[Response(status: 204)]
    public function delete(Request $request, Project $project, Server $server, Site $site, Ssl $ssl): \Illuminate\Http\Response
    {
        $this->authorize('delete', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        app(DeleteSSL::class)->delete($ssl);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?Site $site = null, ?Ssl $ssl = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($site && $site->server_id !== $server->id) {
            abort(404, 'Site not found in server');
        }

        if ($site && $ssl && $ssl->site_id !== $site->id) {
            abort(404, 'SSL certificate not found in site');
        }
    }
}
