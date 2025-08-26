<?php

namespace App\Http\Controllers;

use App\Actions\Site\Deploy;
use App\Actions\Site\Rollback;
use App\Actions\Site\UpdateDeploymentScript;
use App\Actions\Site\UpdateEnv;
use App\Actions\Site\UpdateLoadBalancer;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Exceptions\FailedToDestroyGitHook;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHError;
use App\Http\Resources\DeploymentResource;
use App\Http\Resources\DeploymentScriptResource;
use App\Http\Resources\LoadBalancerServerResource;
use App\Http\Resources\ServerLogResource;
use App\Models\Deployment;
use App\Models\DeploymentScript;
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
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('/servers/{server}/sites/{site}')]
#[Middleware(['auth', 'has-project'])]
class ApplicationController extends Controller
{
    #[Get('/', name: 'application')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('view', [$site, $server]);

        $site->ensureDeploymentScriptsExist();

        $deploymentScript = $site->deploymentScript;
        $buildScript = $site->buildScript;
        $preFlightScript = $site->preFlightScript;

        return Inertia::render('application/index', [
            'logs' => ServerLogResource::collection($site->logs()->latest()->simplePaginate(config('web.pagination_size'))),
            'deployments' => DeploymentResource::collection($site->deployments()->latest()->simplePaginate(config('web.pagination_size'))),
            'deploymentScript' => new DeploymentScriptResource($deploymentScript),
            'buildScript' => $buildScript ? new DeploymentScriptResource($buildScript) : null,
            'preFlightScript' => $preFlightScript ? new DeploymentScriptResource($preFlightScript) : null,
            'loadBalancerServers' => LoadBalancerServerResource::collection($site->loadBalancerServers),
        ]);
    }

    #[Put('/deployment-scripts/{deploymentScript}', name: 'application.update-deployment-script')]
    public function updateScript(Request $request, Server $server, Site $site, DeploymentScript $deploymentScript): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateDeploymentScript::class)->update($deploymentScript, $request->input());

        return back()->with('success', 'Deployment script updated successfully.');
    }

    /**
     * @throws DeploymentScriptIsEmptyException
     */
    #[Post('/deploy', name: 'application.deploy')]
    public function deploy(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(Deploy::class)->run($site);

        return back()->with('info', 'Deployment started, please wait...');
    }

    #[Post('/rollback/{deployment}', name: 'application.rollback')]
    public function rollback(Server $server, Site $site, Deployment $deployment): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if ($deployment->site_id !== $site->id) {
            return back()->with('error', 'Invalid deployment selected for rollback.');
        }

        app(Rollback::class)->run($deployment);

        return back()->with('info', 'Rollback started, please wait...');
    }

    #[Delete('/deployments/{deployment}', name: 'application.deployments.destroy')]
    public function destroyDeployment(Server $server, Site $site, Deployment $deployment): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if ($deployment->site_id !== $site->id) {
            abort(404);
        }

        $deployment->remove();

        return back()->with('success', 'Deployment removed successfully.');
    }

    #[Get('/env', name: 'application.env')]
    public function env(Request $request, Server $server, Site $site): JsonResponse
    {
        $this->authorize('view', [$site, $server]);

        if ($request->input('env')) {
            $site->jsonUpdate('type_data', 'env_path', $request->input('env'), false);
        }

        $env = $site->getEnv();

        return response()->json([
            'env' => $env,
        ]);
    }

    /**
     * @throws SSHError
     */
    #[Put('/env', name: 'application.update-env')]
    public function updateEnv(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateEnv::class)->update($site, $request->input());

        return back()->with('success', '.env file updated successfully.');
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    #[Post('/enable-auto-deployment', name: 'application.enable-auto-deployment')]
    public function enableAutoDeployment(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if (! $site->sourceControl) {
            return back()->with('error', 'Cannot find source control for this site.');
        }

        $site->enableAutoDeployment();

        return back()->with('success', 'Auto deployment enabled successfully.');
    }

    /**
     * @throws SourceControlIsNotConnected
     * @throws FailedToDestroyGitHook
     */
    #[Post('/disable-auto-deployment', name: 'application.disable-auto-deployment')]
    public function disableAutoDeployment(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        if (! $site->sourceControl) {
            return back()->with('error', 'Cannot find source control for this site.');
        }

        $site->disableAutoDeployment();

        return back()->with('success', 'Auto deployment disabled successfully.');
    }

    #[Post('/load-balancer', name: 'application.update-load-balancer')]
    public function updateLoadBalancer(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('update', [$site, $server]);

        app(UpdateLoadBalancer::class)->update($site, $request->input());

        return back()->with('success', 'Load balancer updated successfully.');
    }
}
