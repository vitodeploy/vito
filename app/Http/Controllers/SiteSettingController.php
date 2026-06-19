<?php

namespace App\Http\Controllers;

use App\Actions\Site\DeleteSite;
use App\Actions\Site\PreviewVhost;
use App\Actions\Site\UpdateBasicAuth;
use App\Actions\Site\UpdateBranch;
use App\Actions\Site\UpdatePHPSettings;
use App\Actions\Site\UpdatePHPVersion;
use App\Actions\Site\UpdatePort;
use App\Actions\Site\UpdateSiteStats;
use App\Actions\Site\UpdateSiteWorkerEnvironment;
use App\Actions\Site\UpdateSourceControl;
use App\Actions\Site\UpdateStartCommand;
use App\Actions\Site\UpdateVhost;
use App\Actions\Site\UpdateVhostGeneration;
use App\Actions\Site\UpdateVhostTemplate;
use App\Actions\Site\UpdateWebDirectory;
use App\Actions\Site\WorkerStartCommandUpdateResult;
use App\Actions\Webserver\GenerateCaddyConfig;
use App\Actions\Webserver\GenerateNginxConfig;
use App\Actions\Worker\WorkerEnvironmentUpdateResult;
use App\Exceptions\SSHError;
use App\Helpers\EnvParser;
use App\Http\Resources\SourceControlResource;
use App\Models\Server;
use App\Models\Site;
use App\SiteTypes\AbstractProxiedSiteType;
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
    #[Patch('/php-settings', name: 'site-settings.update-php-settings')]
    public function updatePHPSettings(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        abort_unless($site->supportsPhpSettings(), 404);

        app(UpdatePHPSettings::class)->update($site, $request->input());

        return back()->with('success', 'PHP settings updated successfully.');
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

    /**
     * @throws SSHError
     */
    #[Patch('/port', name: 'site-settings.update-port')]
    public function updatePort(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdatePort::class)->update($site, $request->input());

        return back()->with('success', 'Port updated and VHost regenerated.');
    }

    /**
     * @throws SSHError
     */
    #[Patch('/start-command', name: 'site-settings.update-start-command')]
    public function updateStartCommand(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        $result = app(UpdateStartCommand::class)->update($site, $request->input());

        return match ($result) {
            WorkerStartCommandUpdateResult::PreFirstDeploy => back()->with(
                'info',
                'Start command saved. It will be used when the site is first deployed.',
            ),
            WorkerStartCommandUpdateResult::PendingRestart => back()->with(
                'warning',
                'Start command updated. The worker is still running with the previous command — restart the worker or deploy to apply.',
            ),
            WorkerStartCommandUpdateResult::Restarting => back()->with(
                'info',
                'Start command updated. The worker is restarting to apply the change.',
            ),
        };
    }

    #[Get('/worker-env', name: 'site-settings.worker-env')]
    public function workerEnv(Server $server, Site $site): JsonResponse
    {
        $this->authorize('view', [$site, $server]);

        $type = $site->type();
        if (! $type instanceof AbstractProxiedSiteType) {
            abort(404);
        }

        return response()->json([
            'variables' => EnvParser::maskSecrets(
                $type->bootstrapWorker()->environment ?? $site->worker_environment ?? []
            ),
        ]);
    }

    /**
     * @throws SSHError
     */
    #[Patch('/worker-env', name: 'site-settings.update-worker-env')]
    public function updateWorkerEnv(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if (! $site->type() instanceof AbstractProxiedSiteType) {
            abort(404);
        }

        $result = app(UpdateSiteWorkerEnvironment::class)->update($site, $request->input());

        return match ($result) {
            WorkerEnvironmentUpdateResult::PreFirstDeploy => back()->with(
                'info',
                'Environment saved. It will be applied when the application worker is created on the first deploy.',
            ),
            WorkerEnvironmentUpdateResult::PendingRestart => back()->with(
                'warning',
                'Environment updated. The worker is still running with the previous variables — restart it or deploy to apply.',
            ),
            WorkerEnvironmentUpdateResult::Restarting => back()->with(
                'info',
                'Environment updated. The worker is restarting to apply the change.',
            ),
        };
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

    /**
     * @throws SSHError
     */
    #[Patch('/basic-auth', name: 'site-settings.update-basic-auth')]
    public function updateBasicAuth(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateBasicAuth::class)->update($site, $request->input());

        return back()->with('success', 'Basic auth settings updated successfully.');
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

    #[Post('/stats/enable', name: 'site-settings.enable-stats')]
    public function enableStats(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateSiteStats::class)->enable($site);

        return back()->with('success', 'Statistics enabled for this site.');
    }

    #[Post('/stats/disable', name: 'site-settings.disable-stats')]
    public function disableStats(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateSiteStats::class)->disable($site);

        return back()->with('success', 'Statistics disabled and historical data erased.');
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
