<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/sites/{site}/features')]
#[Middleware(['auth', 'has-project'])]
class SiteFeatureController extends Controller
{
    #[Get('/', name: 'site-features')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('view', [$site, $server]);

        return Inertia::render('site-features/index', [
        ]);
    }
}
