<?php

namespace App\Http\Controllers;

use App\Actions\PHP\ChangeDefaultCli;
use App\Actions\PHP\GetPHPIni;
use App\Actions\PHP\InstallNewPHP;
use App\Actions\PHP\InstallPHPExtension;
use App\Actions\PHP\UninstallPHP;
use App\Actions\PHP\UpdatePHPIni;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PHPController extends Controller
{
    public function index(Server $server): View
    {
        return view('php.index', [
            'server' => $server,
            'phps' => $server->services()->where('type', 'php')->get(),
            'defaultPHP' => $server->defaultService('php'),
        ]);
    }

    public function install(Server $server, Request $request): HtmxResponse
    {
        try {
            app(InstallNewPHP::class)->install($server, $request->input());

            Toast::success('PHP is being installed!');
        } catch (ValidationException $e) {
            Toast::error($e->getMessage());
        }

        return htmx()->back();
    }

    public function installExtension(Server $server, Request $request): HtmxResponse
    {
        app(InstallPHPExtension::class)->install($server, $request->input());

        Toast::success('PHP extension is being installed! Check the logs');

        return htmx()->back();
    }

    public function defaultCli(Server $server, Request $request): HtmxResponse
    {
        app(ChangeDefaultCli::class)->change($server, $request->input());

        Toast::success('Default PHP CLI is being changed!');

        return htmx()->back();
    }

    public function getIni(Server $server, Request $request): RedirectResponse
    {
        $ini = app(GetPHPIni::class)->getIni($server, $request->input());

        return back()->with('ini', $ini);
    }

    public function updateIni(Server $server, Request $request): RedirectResponse
    {
        app(UpdatePHPIni::class)->update($server, $request->input());

        Toast::success('PHP ini updated!');

        return back()->with([
            'ini' => $request->input('ini'),
        ]);
    }

    public function uninstall(Server $server, Request $request): RedirectResponse
    {
        app(UninstallPHP::class)->uninstall($server, $request->input());

        Toast::success('PHP is being uninstalled!');

        return back();
    }
}
