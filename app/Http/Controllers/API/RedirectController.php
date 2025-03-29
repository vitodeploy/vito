<?php

namespace App\Http\Controllers\API;

use App\Actions\Redirect\CreateRedirect;
use App\Actions\Redirect\DeleteRedirect;
use App\Http\Controllers\Controller;
use App\Http\Resources\RedirectResource;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response as HttpResponse;
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

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}/redirects')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'redirects')]
class RedirectController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites.redirects.index', middleware: 'ability:read')]
    #[Endpoint(title: 'index', description: 'Get all redirects.')]
    #[ResponseFromApiResource(RedirectResource::class, Redirect::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('view', [Redirect::class, $site, $server]);

        $this->validateRoute($project, $server, $site);

        return RedirectResource::collection($site->redirects()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.sites.redirects.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new redirect.')]
    #[BodyParam(name: 'from', required: true)]
    #[BodyParam(name: 'to', required: true)]
    #[BodyParam(name: 'mode', required: true, enum: [301, 302, 307, 308])]
    #[Response(status: 200)]
    public function create(Request $request, Project $project, Server $server, Site $site): RedirectResource
    {
        $this->authorize('create', [Redirect::class, $site, $server]);

        $this->validateRoute($project, $server, $site);

        $this->validate($request, CreateRedirect::rules($site));

        $redirect = app(CreateRedirect::class)->create($site, $request->all());

        return new RedirectResource($redirect);
    }

    #[Delete('/{redirect}', name: 'api.projects.servers.sites.redirects.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete a redirect.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, Site $site, Redirect $redirect): HttpResponse
    {
        $this->authorize('delete', [Redirect::class, $site, $server]);

        $this->validateRoute($project, $server, $site);

        app(DeleteRedirect::class)->delete($site, $redirect);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, Site $site): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($site->server_id !== $server->id) {
            abort(404, 'Site not found in server');
        }
    }
}
