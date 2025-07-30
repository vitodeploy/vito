<?php

namespace App\Http\Controllers;

use App\Actions\PHP\ChangeDefaultCli;
use App\Actions\PHP\GetPHPIni;
use App\Actions\PHP\InstallPHPExtension;
use App\Actions\PHP\UpdatePHPIni;
use App\Exceptions\SSHError;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/php')]
#[Middleware(['auth', 'has-project'])]
class PHPController extends Controller
{
    #[Get('/', name: 'php')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Service::class, $server]);

        if (! $server->php()) {
            abort(404);
        }

        $installedVersions = Service::query()
            ->where('type', 'php')
            ->where('server_id', $server->id)
            ->simplePaginate(config('web.pagination_size'));

        return Inertia::render('php/index', [
            'installedVersions' => ServiceResource::collection($installedVersions),
        ]);
    }

    #[Get('/{service}/ini', name: 'php.ini')]
    public function ini(Request $request, Server $server, Service $service): JsonResponse
    {
        $this->authorize('view', $service);

        $ini = app(GetPHPIni::class)->getIni($server, $request->input());

        return response()->json([
            'ini' => $ini,
        ]);
    }

    #[Patch('/{service}/ini', name: 'php.ini.update')]
    public function updateIni(Request $request, Server $server, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        app(UpdatePHPIni::class)->update($server, $request->input());

        return back()->with('success', 'PHP ini file updated successfully.');
    }

    #[Post('/{service}/install-extension', name: 'php.install-extension')]
    public function installExtension(Request $request, Server $server, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        app(InstallPHPExtension::class)->install($server, $request->input());

        return back()->with('info', 'PHP extension is being installed.');
    }

    /**
     * @throws SSHError
     */
    #[Post('/{service}/default-cli', name: 'php.default-cli')]
    public function defaultCli(Request $request, Server $server, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        app(ChangeDefaultCli::class)->change($server, $request->input());

        return back()->with('success', 'Default PHP CLI changed to '.$service->version.'.');
    }
}
