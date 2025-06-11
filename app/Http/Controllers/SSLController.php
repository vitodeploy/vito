<?php

namespace App\Http\Controllers;

use App\Actions\SSL\ActivateSSL;
use App\Actions\SSL\CreateSSL;
use App\Actions\SSL\DeactivateSSL;
use App\Actions\SSL\DeleteSSL;
use App\Http\Resources\SslResource;
use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/sites/{site}/ssl')]
#[Middleware(['auth', 'has-project'])]
class SSLController extends Controller
{
    #[Get('/', name: 'ssls')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [Ssl::class, $site, $server]);

        return Inertia::render('ssls/index', [
            'ssls' => SslResource::collection($site->ssls()->latest()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'ssls.store')]
    public function store(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('create', [Ssl::class, $site, $server]);

        app(CreateSSL::class)->create($site, $request->input());

        return back()
            ->with('info', 'Setting up SSL.');
    }

    #[Delete('/{ssl}', name: 'ssls.destroy')]
    public function destroy(Server $server, Site $site, Ssl $ssl): RedirectResponse
    {
        $this->authorize('delete', [$ssl, $site, $server]);

        app(DeleteSSL::class)->delete($ssl);

        return back()
            ->with('success', 'SSL deleted successfully.');
    }

    #[Post('/enable-force-ssl', name: 'ssls.enable-force-ssl')]
    public function enableForceSSL(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        $site->force_ssl = true;
        $site->save();
        $site->webserver()->updateVHost($site, regenerate: [
            'force-ssl',
        ]);

        return back()
            ->with('success', 'Force SSL enabled successfully.');
    }

    #[Post('/disable-force-ssl', name: 'ssls.disable-force-ssl')]
    public function disableForceSSL(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        $site->force_ssl = false;
        $site->save();
        $site->webserver()->updateVHost($site, regenerate: [
            'force-ssl',
        ]);

        return back()
            ->with('success', 'Force SSL disabled successfully.');
    }

    #[Post('/{ssl}/activate', name: 'ssls.activate')]
    public function activate(Server $server, Site $site, Ssl $ssl): RedirectResponse
    {
        $this->authorize('update', [$ssl, $site, $server]);

        app(ActivateSSL::class)->activate($ssl);

        return back()
            ->with('success', 'SSL activated successfully.');
    }

    #[Post('/{ssl}/deactivate', name: 'ssls.deactivate')]
    public function deactivate(Server $server, Site $site, Ssl $ssl): RedirectResponse
    {
        $this->authorize('update', [$ssl, $site, $server]);

        app(DeactivateSSL::class)->deactivate($ssl);

        return back()
            ->with('success', 'SSL deactivated successfully.');
    }
}
