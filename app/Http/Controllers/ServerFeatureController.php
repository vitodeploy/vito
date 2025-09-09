<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\ServerFeatures\ActionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/features')]
#[Middleware(['auth', 'has-project'])]
class ServerFeatureController extends Controller
{
    #[Get('/', name: 'server-features')]
    public function index(Server $server): Response
    {
        $this->authorize('view', [$server]);

        return Inertia::render('server-features/index', [
            'features' => $server->features(),
        ]);
    }

    #[Post('/{feature}/{action}', name: 'server-features.action')]
    public function action(Request $request, Server $server, string $feature, string $action): RedirectResponse
    {
        $this->authorize('update', [$server]);

        $handler = config('server.features.'.$feature.'.actions.'.$action.'.handler');
        if ($handler && class_exists($handler)) {
            /** @var ActionInterface $actionHandler */
            $actionHandler = new $handler($server);
            $actionHandler->handle($request);
        }

        return back();
    }
}
