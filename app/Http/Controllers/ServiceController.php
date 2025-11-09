<?php

namespace App\Http\Controllers;

use App\Actions\Service\GetConfigFile;
use App\Actions\Service\Install;
use App\Actions\Service\Manage;
use App\Actions\Service\Uninstall;
use App\Actions\Service\UpdateConfigFile;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/services')]
#[Middleware(['auth', 'has-project'])]
class ServiceController extends Controller
{
    #[Get('/', name: 'services')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Service::class, $server]);

        $services = $server->services()->simplePaginate(config('web.pagination_size'));

        return Inertia::render('services/index', [
            'services' => ServiceResource::collection($services),
        ]);
    }

    #[Get('{service}/versions', name: 'services.versions')]
    public function versions(Server $server, string $service): JsonResponse
    {
        $this->authorize('viewAny', [Service::class, $server]);

        $versions = [];
        $services = $server->services()->where('type', $service)->get(['version']);
        /** @var Service $service */
        foreach ($services as $service) {
            $versions[] = $service->version;
        }

        return response()->json($versions);
    }

    #[Post('/', name: 'services.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [Service::class, $server]);

        app(Install::class)->install($server, $request->input());

        return back()->with('success', __(':service is being installed.', [
            'service' => $request->input('name'),
        ]));
    }

    #[Post('/{service}/start', name: 'services.start')]
    public function start(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('start', $service);

        app(Manage::class)->start($service);

        return back()->with('success', __(':service is being started.', [
            'service' => $service->name,
        ]));
    }

    #[Post('/{service}/restart', name: 'services.restart')]
    public function restart(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('restart', $service);

        app(Manage::class)->restart($service);

        return back()->with('success', __(':service is being restarted.', [
            'service' => $service->name,
        ]));
    }

    #[Post('/{service}/reload', name: 'services.reload')]
    public function reload(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('reload', $service);

        app(Manage::class)->reload($service);

        return back()->with('success', __(':service is being reloaded.', [
            'service' => $service->name,
        ]));
    }

    #[Post('/{service}/stop', name: 'services.stop')]
    public function stop(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('stop', $service);

        app(Manage::class)->stop($service);

        return back()->with('success', __(':service is being stopped.', [
            'service' => $service->name,
        ]));
    }

    #[Post('/{service}/enable', name: 'services.enable')]
    public function enable(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('enable', $service);

        app(Manage::class)->enable($service);

        return back()->with('success', __(':service is being enabled.', [
            'service' => $service->name,
        ]));
    }

    #[Post('/{service}/disable', name: 'services.disable')]
    public function disable(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('disable', $service);

        app(Manage::class)->disable($service);

        return back()->with('success', __(':service is being disabled.', [
            'service' => $service->name,
        ]));
    }

    #[Delete('/{service}', name: 'services.destroy')]
    public function destroy(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        app(Uninstall::class)->uninstall($service);

        return back()->with('warning', __(':service is being uninstalled.', [
            'service' => $service->name,
        ]));
    }

    #[Get('/{service}/version', name: 'services.version')]
    public function version(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('view', $service);

        $service->installed_version = $service->handler()->version();
        $service->save();

        return back()->with('success', __('Fetched installed version for :service', [
            'service' => $service->name,
        ]));
    }

    #[Get('/{service}/config', name: 'services.config')]
    public function getConfig(Request $request, Server $server, Service $service): JsonResponse
    {
        $this->authorize('view', $service);

        $content = app(GetConfigFile::class)->get($service, $request->input());

        return response()->json([
            'content' => $content,
        ]);
    }

    #[Patch('/{service}/config', name: 'services.config.update')]
    public function updateConfig(Request $request, Server $server, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        app(UpdateConfigFile::class)->update($service, $request->input());

        return back()->with('success', __('Config file updated successfully.'));
    }
}
