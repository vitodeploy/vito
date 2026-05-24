<?php

namespace App\Http\Controllers;

use App\Actions\Site\Tooling\GetSiteTooling;
use App\Actions\Site\Tooling\InstallSiteTooling;
use App\Actions\Site\Tooling\UninstallSiteTooling;
use App\Models\Server;
use App\Models\Site;
use App\Tooling\ToolingRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Prefix('/servers/{server}/sites/{site}/tooling')]
#[Middleware(['auth', 'has-project'])]
class SiteToolingController extends Controller
{
    #[Get('/', name: 'site-tooling')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('view', [$site, $server]);
        $this->ensureReadyAndIsolated($server, $site);

        return Inertia::render('site-tooling/index', app(GetSiteTooling::class)->get($site));
    }

    #[Post('/{tool}', name: 'site-tooling.install')]
    public function install(Request $request, Server $server, Site $site, string $tool): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);
        $this->ensureReadyAndIsolated($server, $site);
        $this->ensureKnownTool($tool);

        app(InstallSiteTooling::class)->install($site, $tool, $request->all());

        return back()->with('info', "Installing {$tool}, please wait…");
    }

    #[Delete('/{tool}', name: 'site-tooling.uninstall')]
    public function uninstall(Server $server, Site $site, string $tool): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);
        $this->ensureReadyAndIsolated($server, $site);
        $this->ensureKnownTool($tool);

        app(UninstallSiteTooling::class)->uninstall($site, $tool);

        return back()->with('info', "Uninstalling {$tool}, please wait…");
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureReadyAndIsolated(Server $server, Site $site): void
    {
        if (! $site->isReady() || ! $site->isIsolated()) {
            throw new AuthorizationException;
        }
    }

    private function ensureKnownTool(string $tool): void
    {
        if (! ToolingRegistry::find($tool)) {
            throw new NotFoundHttpException("Unknown tool: {$tool}");
        }
    }
}
