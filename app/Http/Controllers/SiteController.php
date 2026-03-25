<?php

namespace App\Http\Controllers;

use App\Actions\Site\CreateSite;
use App\Actions\Site\DisableSsl;
use App\Actions\Site\EnableSsl;
use App\Actions\Site\GetSites;
use App\Helpers\QueryBuilder;
use App\Http\Resources\ServerLogResource;
use App\Http\Resources\SiteResource;
use App\Models\Server;
use App\Models\Site;
use App\Tables\SiteTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Throwable;

#[Middleware(['auth', 'has-project'])]
class SiteController extends Controller
{
    #[Get('/sites', name: 'sites.all')]
    public function index(): Response
    {
        $this->authorize('viewAny', user()->currentProject);

        return Inertia::render('sites/index', [
            'sites' => SiteTable::make(user()->currentProject->sites())->simplePaginate(),
        ]);
    }

    #[Get('/servers/{server}/sites', name: 'sites')]
    public function server(Server $server): Response
    {
        $this->authorize('viewAny', [Site::class, $server]);

        return Inertia::render('sites/index', [
            'sites' => SiteTable::make($server->sites())->forServer($server)->simplePaginate(),
        ]);
    }

    #[Get('/servers/{server}/sites-json', name: 'sites.json')]
    public function json(Request $request, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Site::class, $server]);

        $sites = app(GetSites::class)->get($server, $request->input(), 10);

        return SiteResource::collection($sites);
    }

    /**
     * @throws Throwable
     */
    #[Post('/servers/{server}/sites/', name: 'sites.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [Site::class, $server]);

        $site = app(CreateSite::class)->create($server, $request->all());

        return redirect()->route('application', ['server' => $server, 'site' => $site])
            ->with('info', 'Installing site, please wait...');
    }

    #[Post('/servers/{server}/sites/{site}/switch', name: 'sites.switch')]
    public function switch(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('view', [$site, $server]);

        $previousUrl = URL::previous();
        $previousRequest = Request::create($previousUrl);
        $previousRoute = app('router')->getRoutes()->match($previousRequest);

        if ($previousRoute->hasParameter('site')) {
            if (count($previousRoute->parameters()) > 2) {
                return redirect()->route('application', ['server' => $server->id, 'site' => $site->id]);
            }

            return redirect()->route($previousRoute->getName(), ['server' => $server, 'site' => $site->id]);
        }

        return redirect()->route('application', ['server' => $server->id, 'site' => $site->id]);
    }

    #[Post('/servers/{server}/sites/{site}/enable-ssl', name: 'sites.enable-ssl')]
    public function enableSsl(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(EnableSsl::class)->enable($site);

        return back()
            ->with('success', 'SSL enabled successfully.');
    }

    #[Post('/servers/{server}/sites/{site}/disable-ssl', name: 'sites.disable-ssl')]
    public function disableSsl(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(DisableSsl::class)->disable($site);

        return back()
            ->with('success', 'SSL disabled successfully.');
    }

    #[Get('/servers/{server}/sites/{site}/logs', name: 'sites.logs')]
    public function logs(Server $server, Site $site): Response
    {
        $this->authorize('view', [$site, $server]);

        $logs = $site->logs()->latest();
        $logs = QueryBuilder::for($logs)
            ->searchableFields(['name'])
            ->query()
            ->simplePaginate(config('web.pagination_size'), pageName: 'logsPage');

        return Inertia::render('sites/logs', [
            'logs' => ServerLogResource::collection($logs),
        ]);
    }
}
