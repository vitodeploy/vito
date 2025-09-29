<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Plugins\ClearLogs;
use App\Actions\Plugins\DisablePlugin;
use App\Actions\Plugins\DiscoverPlugins;
use App\Actions\Plugins\EnablePlugin;
use App\Actions\Plugins\Github\CheckForUpdates;
use App\Actions\Plugins\Github\InstallGithubPlugin;
use App\Actions\Plugins\Github\UpdateGithubPlugin;
use App\Actions\Plugins\InstallPlugin;
use App\Actions\Plugins\UninstallPlugin;
use App\Http\Controllers\Controller;
use App\Models\Plugin;
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
use Throwable;

#[Prefix('admin/plugins')]
#[Middleware(['auth', 'must-be-admin'])]
class PluginController extends Controller
{
    #[Get('/', name: 'plugins')]
    public function index(DiscoverPlugins $pluginDiscovery): Response
    {
        $pluginDiscovery->handle();
        $plugins = Plugin::with(['errors' => function ($query) {
            $query->latest()->limit(10);
        }])->get();

        return Inertia::render('plugins/index', [
            'plugins' => $plugins,
        ]);
    }

    #[Patch('/disable', name: 'plugins.disable')]
    public function disable(Request $request, DisablePlugin $action): RedirectResponse
    {
        $data = $this->validate($request, ['id' => 'required']);
        if (config('app.demo')) {
            return back()->with('error', 'Plugins are disabled in demo mode.');
        }

        try {
            $plugin = Plugin::findOrFail($data['id']);
            $action->handle($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin '$plugin->name' disabled");
    }

    #[Patch('/enable', name: 'plugins.enable')]
    public function enable(Request $request, EnablePlugin $action): RedirectResponse
    {
        $data = $this->validate($request, ['id' => 'required']);
        if (config('app.demo')) {
            return back()->with('error', 'Plugins are disabled in demo mode.');
        }

        try {
            $plugin = Plugin::findOrFail($data['id']);
            $action->handle($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin '$plugin->name' enabled");
    }

    #[Post('/install/github', name: 'plugins.install.github')]
    public function installGithubPlugin(Request $request, InstallGithubPlugin $action): RedirectResponse
    {
        $data = $this->validate($request, ['url' => 'required']);
        try {
            $plugin = $action->handle($data['url']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin '$plugin->name' installed");
    }

    #[Patch('/install', name: 'plugins.install')]
    public function install(Request $request, InstallPlugin $action): RedirectResponse
    {
        $data = $this->validate($request, ['id' => 'required']);
        if (config('app.demo')) {
            return back()->with('error', 'Plugins are disabled in demo mode.');
        }

        try {
            $plugin = Plugin::findOrFail($data['id']);
            $action->handle($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin '$plugin->name' installed");
    }

    #[Get('/updates', name: 'plugins.updates')]
    public function checkForUpdates(Request $request, CheckForUpdates $action): RedirectResponse
    {
        $action->handle();

        return back()->with('success', 'Retrieved latest plugin releases');
    }

    #[Patch('/update', name: 'plugins.update')]
    public function update(Request $request, UpdateGithubPlugin $action)
    {
        $data = $this->validate($request, ['id' => 'required']);
        if (config('app.demo')) {
            return back()->with('error', 'Plugins are disabled in demo mode.');
        }

        try {
            $plugin = Plugin::findOrFail($data['id']);
            $action->handle($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin '$plugin->name' updated");
    }

    #[Delete('/uninstall', name: 'plugins.uninstall')]
    public function uninstall(Request $request, UninstallPlugin $action): RedirectResponse
    {
        $data = $this->validate($request, ['id' => 'required']);
        if (config('app.demo')) {
            return back()->with('error', 'Plugins are disabled in demo mode.');
        }

        try {
            $plugin = Plugin::findOrFail($data['id']);
            $action->handle($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin '$plugin->name' uninstalled");
    }

    #[Delete('/logs', name: 'plugins.logs')]
    public function clearLogs(Request $request, ClearLogs $action): RedirectResponse
    {
        $data = $this->validate($request, ['id' => 'required']);
        if (config('app.demo')) {
            return back()->with('error', 'Plugins are disabled in demo mode.');
        }

        try {
            $plugin = Plugin::findOrFail($data['id']);
            $action->handle($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $displayName = $plugin->name ?? $plugin->folder;

        return back()->with('success', "Plugin '$displayName' logs cleared");
    }
}
