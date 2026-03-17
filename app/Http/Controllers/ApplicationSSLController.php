<?php

namespace App\Http\Controllers;

use App\Actions\Application\DeployApplication;
use App\Actions\SSL\ActivateSSL;
use App\Actions\SSL\CreateSSL;
use App\Actions\SSL\DeactivateSSL;
use App\Actions\SSL\DeleteSSL;
use App\Models\Application;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/applications/{application}/ssl')]
#[Middleware(['auth', 'has-project'])]
class ApplicationSSLController extends Controller
{
    #[Post('/', name: 'application-ssls.store')]
    public function store(Request $request, Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        app(CreateSSL::class)->create($application, $request->input());

        return back()->with('info', 'Setting up SSL.');
    }

    #[Delete('/{ssl}', name: 'application-ssls.destroy')]
    public function destroy(Server $server, Application $application, Ssl $ssl): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        if ($ssl->application_id !== $application->id) {
            abort(404);
        }

        app(DeleteSSL::class)->delete($ssl);

        return back()->with('success', 'SSL deleted successfully.');
    }

    #[Post('/enable-force-ssl', name: 'application-ssls.enable-force-ssl')]
    public function enableForceSSL(Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        $application->force_ssl = true;
        $application->save();

        app(DeployApplication::class)->deploy($application);

        return back()->with('success', 'Force SSL enabled successfully.');
    }

    #[Post('/disable-force-ssl', name: 'application-ssls.disable-force-ssl')]
    public function disableForceSSL(Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        $application->force_ssl = false;
        $application->save();

        app(DeployApplication::class)->deploy($application);

        return back()->with('success', 'Force SSL disabled successfully.');
    }

    #[Post('/{ssl}/activate', name: 'application-ssls.activate')]
    public function activate(Server $server, Application $application, Ssl $ssl): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        if ($ssl->application_id !== $application->id) {
            abort(404);
        }

        app(ActivateSSL::class)->activate($ssl);

        return back()->with('success', 'SSL activated successfully.');
    }

    #[Post('/{ssl}/deactivate', name: 'application-ssls.deactivate')]
    public function deactivate(Server $server, Application $application, Ssl $ssl): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        if ($ssl->application_id !== $application->id) {
            abort(404);
        }

        app(DeactivateSSL::class)->deactivate($ssl);

        return back()->with('success', 'SSL deactivated successfully.');
    }
}
