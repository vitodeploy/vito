<?php

namespace App\Http\Controllers;

use App\Actions\Server\Security\CheckSecurityState;
use App\Actions\Server\Security\ConfigureFail2ban;
use App\Actions\Server\Security\ManageAutoUpdate;
use App\Actions\Server\Security\ManagePasswordAuth;
use App\Actions\Server\Security\ManageRootLogin;
use App\Actions\Service\Install;
use App\Actions\Service\Uninstall;
use App\Enums\SecurityControlStatus;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
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

#[Prefix('servers/{server}/security')]
#[Middleware(['auth', 'has-project'])]
class SecurityController extends Controller
{
    #[Get('/', name: 'security')]
    public function index(Server $server): Response
    {
        $this->authorize('view', $server);

        $state = $server->securityState();
        $passwordStatus = SecurityControlStatus::from($state['password_authentication']['status']);
        $rootLoginStatus = SecurityControlStatus::from($state['root_login']['status']);
        $fail2ban = $server->fail2ban();
        $firewall = $server->firewall();

        return Inertia::render('security/index', [
            'score' => $server->securityScore(),
            'autoUpdate' => [
                'enabled' => (bool) $server->auto_update,
                'schedule' => $server->auto_update_schedule,
            ],
            'passwordAuth' => [
                'enabled' => $state['password_authentication']['enabled'],
                'detected' => $state['password_authentication']['detected'],
                'status' => $passwordStatus->getText(),
                'status_color' => $passwordStatus->getColor(),
            ],
            'rootLogin' => [
                'enabled' => $state['root_login']['enabled'],
                'detected' => $state['root_login']['detected'],
                'status' => $rootLoginStatus->getText(),
                'status_color' => $rootLoginStatus->getColor(),
                'manageable' => $server->getSshUser() !== 'root',
            ],
            'fail2ban' => $fail2ban ? new ServiceResource($fail2ban) : null,
            'firewall' => $firewall ? new ServiceResource($firewall) : null,
        ]);
    }

    #[Post('/auto-update', name: 'security.auto-update')]
    public function autoUpdate(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        app(ManageAutoUpdate::class)->update($server, $request->all());

        return back()->with('success', 'Automatic updates settings saved.');
    }

    #[Post('/password-auth', name: 'security.password-auth')]
    public function passwordAuth(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(ManagePasswordAuth::class)->update($server, $request->all());

        return back()->with('info', 'Updating SSH password authentication.');
    }

    #[Post('/root-login', name: 'security.root-login')]
    public function rootLogin(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(ManageRootLogin::class)->update($server, $request->all());

        return back()->with('info', 'Updating root SSH login.');
    }

    #[Post('/check', name: 'security.check')]
    public function check(Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(CheckSecurityState::class)->check($server);

        return back()->with('info', 'Checking server security state.');
    }

    #[Post('/firewall', name: 'security.firewall.install')]
    public function installFirewall(Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        if (! $server->firewall()) {
            app(Install::class)->install($server, [
                'name' => 'ufw',
                'version' => 'latest',
            ]);
        }

        return back()->with('info', 'Installing the firewall.');
    }

    #[Post('/fail2ban', name: 'security.fail2ban.install')]
    public function installFail2ban(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(Install::class)->install($server, array_merge($request->all(), [
            'name' => 'fail2ban',
            'version' => 'latest',
        ]));

        return back()->with('info', 'Installing Fail2ban.');
    }

    #[Patch('/fail2ban', name: 'security.fail2ban.update')]
    public function configureFail2ban(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(ConfigureFail2ban::class)->configure($server, $request->all());

        return back()->with('info', 'Updating Fail2ban configuration.');
    }

    #[Delete('/fail2ban', name: 'security.fail2ban.destroy')]
    public function uninstallFail2ban(Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        if ($service = $server->fail2ban()) {
            app(Uninstall::class)->uninstall($service);
        }

        return back()->with('info', 'Uninstalling Fail2ban.');
    }
}
