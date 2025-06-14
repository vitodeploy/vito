<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use App\SiteFeatures\ActionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
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
            'features' => $site->features(),
        ]);
    }

    #[Post('/{feature}/{action}', name: 'site-features.action')]
    public function action(Request $request, Server $server, Site $site, string $feature, string $action): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        $handler = config('site.types.'.$site->type.'.features.'.$feature.'.actions.'.$action.'.handler');
        if ($handler && class_exists($handler)) {
            /** @var ActionInterface $actionHandler */
            $actionHandler = new $handler($site);
            $actionHandler->handle($request);
        }

        return back();
    }
}
