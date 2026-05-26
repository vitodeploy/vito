<?php

namespace App\Http\Controllers;

use App\Actions\SiteStats\GetSiteStats;
use App\Jobs\Site\RefreshSiteStatsJob;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/sites/{site}/stats')]
#[Middleware(['auth', 'has-project'])]
class SiteStatsController extends Controller
{
    #[Get('/', name: 'site-stats')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('view', [$site, $server]);

        return Inertia::render('sites/stats', [
            'hasStatsService' => (bool) $server->service('log_analysis'),
            'statsEnabled' => $site->statsEnabled(),
        ]);
    }

    #[Get('/json', name: 'site-stats.json')]
    public function json(Request $request, Server $server, Site $site): JsonResponse
    {
        $this->authorize('view', [$site, $server]);

        abort_unless((bool) $server->service('log_analysis') && $site->statsEnabled(), 404);

        $month = $request->string('month')->toString();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : null;

        return response()->json(app(GetSiteStats::class)->get($site, $month));
    }

    #[Post('/refresh', name: 'site-stats.refresh')]
    public function refresh(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        abort_unless((bool) $server->service('log_analysis') && $site->statsEnabled(), 404);

        dispatch(new RefreshSiteStatsJob($site));

        return back()->with('success', 'Refreshing site statistics...');
    }
}
