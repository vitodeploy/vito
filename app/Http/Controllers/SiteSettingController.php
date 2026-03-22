<?php

namespace App\Http\Controllers;

use App\Actions\Site\DeleteSite;
use App\Actions\Site\PreviewVhost;
use App\Actions\Site\UpdateBranch;
use App\Actions\Site\UpdatePHPVersion;
use App\Actions\Site\UpdateSourceControl;
use App\Actions\Site\UpdateVhost;
use App\Actions\Site\UpdateVhostGeneration;
use App\Actions\Site\UpdateVhostTemplate;
use App\Actions\Site\UpdateWebDirectory;
use App\Actions\Webserver\GenerateCaddyConfig;
use App\Actions\Webserver\GenerateNginxConfig;
use App\Exceptions\SSHError;
use App\Http\Resources\SourceControlResource;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
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

    #[Post('/vhost-preview', name: 'site-settings.vhost-preview')]
    public function vhostPreview(Request $request, Server $server, Site $site): JsonResponse
    {
        $this->authorize('update', [$site, $server]);

        return response()->json([
            'vhost' => app(PreviewVhost::class)->preview($site, $request->input()),
        ]);
    }

    #[Put('/vhost', name: 'site-settings.update-vhost')]
    public function updateVhost(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateVhost::class)->update($site, $request->input());

        return back()->with('success', 'VHost updated successfully.');
    }

    #[Get('/vhost-template', name: 'site-settings.vhost-template')]
    public function vhostTemplate(Server $server, Site $site): JsonResponse
    {
        $this->authorize('update', [$site, $server]);

        $generator = $this->getVhostGenerator($site);

        return response()->json([
            'template' => $site->vhost_template ?? $generator->defaultTemplate(),
        ]);
    }

    #[Put('/vhost-template', name: 'site-settings.update-vhost-template')]
    public function updateVhostTemplate(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateVhostTemplate::class)->update($site, $request->input());

        return back()->with('success', 'VHost template updated successfully.');
    }

    #[Post('/vhost-template/reset', name: 'site-settings.reset-vhost-template')]
    public function resetVhostTemplate(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        $site->vhost_template = null;
        $site->save();
        $site->webserver()->updateVHost($site);

        return back()->with('success', 'VHost template reset to default.');
    }

    #[Patch('/vhost-generation', name: 'site-settings.update-vhost-generation')]
    public function updateVhostGeneration(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateVhostGeneration::class)->update($site, $request->input());

        return back()->with('success', 'VHost generation setting updated successfully.');
    }

    private function getVhostGenerator(Site $site): GenerateNginxConfig|GenerateCaddyConfig
    {
        return $site->webserver()::id() === 'caddy'
            ? app(GenerateCaddyConfig::class)
            : app(GenerateNginxConfig::class);
    }

    #[Post('/force-ssl/enable', name: 'site-settings.enable-force-ssl')]
    public function enableForceSsl(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if (! $site->webserver()->canConfigureSSL()) {
            throw ValidationException::withMessages([
                'force_ssl' => 'Force SSL cannot be changed for this webserver.',
            ]);
        }

        $site->force_ssl = true;
        $site->save();
        $site->webserver()->updateVHost($site);

        return back()->with('success', 'Force SSL enabled successfully.');
    }

    #[Post('/force-ssl/disable', name: 'site-settings.disable-force-ssl')]
    public function disableForceSsl(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if (! $site->webserver()->canConfigureSSL()) {
            throw ValidationException::withMessages([
                'force_ssl' => 'Force SSL cannot be changed for this webserver.',
            ]);
        }

        $site->force_ssl = false;
        $site->save();
        $site->webserver()->updateVHost($site);

        return back()->with('success', 'Force SSL disabled successfully.');
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
