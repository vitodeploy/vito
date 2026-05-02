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
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}/ssls')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class SSLController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites.ssls', middleware: 'ability:read')]
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
    public function show(Project $project, Server $server, Site $site, Ssl $ssl): SslResource
    {
        $this->authorize('view', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        return new SslResource($ssl);
    }

    #[Post('/letsencrypt', name: 'api.projects.servers.sites.ssls.create-letsencrypt', middleware: 'ability:write')]
    public function createLetsEncrypt(Request $request, Project $project, Server $server, Site $site): SslResource
    {
        $this->authorize('create', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $ssl = app(CreateSSL::class)->create($site, array_merge($request->all(), ['type' => SslType::LETSENCRYPT->value]));

        return new SslResource($ssl);
    }

    #[Post('/custom', name: 'api.projects.servers.sites.ssls.create-custom', middleware: 'ability:write')]
    public function createCustom(Request $request, Project $project, Server $server, Site $site): SslResource
    {
        $this->authorize('create', [$project, $server, $site]);

        $this->validateRoute($project, $server, $site);

        $ssl = app(CreateSSL::class)
            ->create($site, array_merge($request->all(), ['type' => SslType::CUSTOM->value]));

        return new SslResource($ssl);
    }

    #[Post('/{ssl}/activate', name: 'api.projects.servers.sites.ssls.activate', middleware: 'ability:write')]
    public function activate(Request $request, Project $project, Server $server, Site $site, Ssl $ssl): SslResource
    {
        $this->authorize('update', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        app(ActivateSSL::class)->activate($ssl);

        return new SslResource($ssl);
    }

    #[Post('/{ssl}/deactivate', name: 'api.projects.servers.sites.ssls.deactivate', middleware: 'ability:write')]
    public function deactivate(Request $request, Project $project, Server $server, Site $site, Ssl $ssl): SslResource
    {
        $this->authorize('update', [$project, $server, $site, $ssl]);

        $this->validateRoute($project, $server, $site, $ssl);

        app(DeactivateSSL::class)->deactivate($ssl);

        return new SslResource($ssl);
    }

    #[Delete('/{ssl}', name: 'api.projects.servers.sites.ssls.delete', middleware: 'ability:write')]
    public function delete(Request $request, Project $project, Server $server, Site $site, Ssl $ssl): Response
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
