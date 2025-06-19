<?php

namespace App\Http\Controllers;

use App\Facades\Plugins;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Throwable;

#[Prefix('settings/plugins')]
#[Middleware(['auth', 'must-be-admin'])]
class PluginController extends Controller
{
    #[Get('/', name: 'plugins')]
    public function index(): Response
    {
        $plugins = [];
        try {
            $plugins = Plugins::all();
        } catch (Throwable $e) {
            report($e);
        }

        return Inertia::render('plugins/index', [
            'plugins' => $plugins,
        ]);
    }

    #[Post('/install', name: 'plugins.install')]
    public function install(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'url' => 'required|url',
        ]);

        if (! composer_path() || ! php_path()) {
            return back()->with('error', 'Use CLI to install plugins.');
        }

        $url = $request->input('url');

        dispatch(function () use ($url) {
            try {
                Plugins::install($url);
            } catch (Throwable $e) {
                //
            }

            Plugins::cleanup();
        })
            ->onConnection('default');

        return back()->with('info', 'Plugin is being installed...');
    }

    #[Delete('/uninstall', name: 'plugins.uninstall')]
    public function uninstall(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|string',
        ]);

        if (! composer_path() || ! php_path()) {
            return back()->with('error', 'Use CLI to uninstall plugins.');
        }

        $name = $request->input('name');

        dispatch(function () use ($name) {
            try {
                Plugins::uninstall($name);
            } catch (Throwable) {
                //
            }

            Plugins::cleanup();
        })
            ->onConnection('default');

        return back()->with('warning', 'Plugin is being uninstalled...');
    }
}
