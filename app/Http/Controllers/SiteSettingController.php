<?php

namespace App\Http\Controllers;

use App\Actions\Site\DeleteSite;
use App\Actions\Site\UpdateAliases;
use App\Actions\Site\UpdateBranch;
use App\Actions\Site\UpdatePHPVersion;
use App\Actions\Site\UpdateSourceControl;
use App\Actions\Site\UpdateWebDirectory;
use App\Exceptions\SSHError;
use App\Http\Resources\SourceControlResource;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('/servers/{server}/sites/{site}/settings')]
#[Middleware(['auth', 'has-project'])]
class SiteSettingController extends Controller
{
    #[Get('/', name: 'site-settings')]
    public function index(Server $server, Site $site): Response
    {
        return Inertia::render('site-settings/index', [
            'sourceControl' => $site->sourceControl ? SourceControlResource::make($site->sourceControl) : null,
        ]);
    }

    /**
     * @throws SSHError
     */
    #[Patch('/branch', name: 'site-settings.update-branch')]
    public function updateBranch(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateBranch::class)->update($site, $request->input());

        return back()->with('success', 'Branch updated successfully.');
    }

    #[Patch('/source-control', name: 'site-settings.update-source-control')]
    public function updateSourceControl(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateSourceControl::class)->update($site, $request->input());

        return back()->with('success', 'Source control updated successfully.');
    }

    /**
     * @throws SSHError
     */
    #[Patch('/aliases', name: 'site-settings.update-aliases')]
    public function updateAliases(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateAliases::class)->update($site, $request->input());

        return back()->with('success', 'Aliases updated successfully.');
    }

    /**
     * @throws SSHError
     */
    #[Patch('/php-version', name: 'site-settings.update-php-version')]
    public function updatePHPVersion(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdatePHPVersion::class)->update($site, $request->input());

        return back()->with('success', 'PHP version updated successfully.');
    }

    /**
     * @throws SSHError
     */
    #[Patch('/web-directory', name: 'site-settings.update-web-directory')]
    public function updateWebDirectory(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateWebDirectory::class)->update($site, $request->input());

        return back()->with('success', 'Web directory updated successfully.');
    }

    #[Get('/vhost', name: 'site-settings.vhost')]
    public function vhost(Server $server, Site $site): JsonResponse
    {
        $this->authorize('update', [$site, $server]);

        return response()->json([
            'vhost' => $site->webserver()->getVHost($site),
        ]);
    }

    #[Put('/vhost', name: 'site-settings.update-vhost')]
    public function updateVhost(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        $this->validate($request, [
            'vhost' => 'required|string',
        ]);

        $site->webserver()->updateVHost($site, $request->input('vhost'));

        return back()->with('success', 'VHost updated successfully.');
    }

    /**
     * @throws SSHError
     */
    #[Delete('/', name: 'site-settings.destroy')]
    public function destroy(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('delete', [$site, $server]);

        app(DeleteSite::class)->delete($site, $request->input());

        return redirect()->route('sites', ['server' => $server])
            ->with('success', 'Site deleted successfully.');
    }
}
