<?php

namespace App\Http\Controllers\API;

use App\Actions\SSL\CreateSSL;
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
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
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
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        $ssls = $site->ssls()
            ->with(['log'])
            ->latest()
            ->simplePaginate(25);

        return SslResource::collection($ssls);
    }

    #[Post('/', name: 'api.projects.servers.sites.ssls.create-letsencrypt', middleware: 'ability:write')]
    #[Endpoint(title: 'create-letsencrypt', description: 'Create a new Let\'s Encrypt SSL certificate.')]
    #[BodyParam(name: 'email', required: true)]
    #[BodyParam(name: 'aliases', type: 'boolean', description: 'Set SSL for site\'s aliases as well')]
    #[ResponseFromApiResource(SslResource::class, Ssl::class)]
    public function createLetsEncrypt(Request $request, Project $project, Server $server, Site $site): SslResource
    {
        $this->authorize('create', [Site::class, $server]);

        $this->validateRoute($project, $server);

        $ssl = app(CreateSSL::class)->create($site, array_merge($request->all(), ['type' => SslType::LETSENCRYPT]));

        return new SslResource($ssl);
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
