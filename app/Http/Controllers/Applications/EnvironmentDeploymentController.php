<?php

namespace App\Http\Controllers\Applications;

use App\Actions\EnvironmentDeployment\CancelEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\EnvironmentDeployment\RollbackEnvironment;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentScript;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentSettings;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class EnvironmentDeploymentController extends Controller
{
    #[Post('/deployments', name: 'environment-deployments.store')]
    public function store(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('create', [EnvironmentDeployment::class, $environment, $application]);
        $deployment = app(DeployEnvironment::class)->run($environment, user());

        return to_route('environment-deployments.show', [$application, $environment, $deployment, ...$request->only('component', 'resource')]);
    }

    #[Post('/deployments/{deployment}/cancel', name: 'environment-deployments.cancel')]
    public function cancel(Application $application, Environment $environment, EnvironmentDeployment $deployment): RedirectResponse
    {
        $this->authorize('update', [$deployment, $environment, $application]);
        app(CancelEnvironmentDeployment::class)->cancel($deployment);

        return back()->with('success', 'Deployment cancelled. In-flight work will finish before the next run starts.');
    }

    #[Post('/deployments/{deployment}/rollback', name: 'environment-deployments.rollback')]
    public function rollback(Request $request, Application $application, Environment $environment, EnvironmentDeployment $deployment): RedirectResponse
    {
        $this->authorize('update', [$deployment, $environment, $application]);
        $run = app(RollbackEnvironment::class)->run($environment, $deployment, user());

        return to_route('environment-deployments.show', [$application, $environment, $run, ...$request->only('component', 'resource')]);
    }

    #[Put('/deployment-settings', name: 'environment-deployments.settings.update')]
    public function updateSettings(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);
        app(UpdateEnvironmentDeploymentSettings::class)->update($environment, $request->all());

        return back()->with('success', 'Deployment settings updated.');
    }

    #[Put('/deployment-scripts', name: 'environment-deployments.scripts.update')]
    public function updateScript(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);
        app(UpdateEnvironmentDeploymentScript::class)->update($environment, $request->all());

        return back()->with('success', 'Deployment script updated.');
    }

    #[Put('/components/{component}/deployment-scripts', name: 'components.deployment-scripts.update')]
    public function updateComponentScript(Request $request, Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('update', [$component, $environment, $application]);
        app(UpdateEnvironmentDeploymentScript::class)->update($environment, $request->all(), $component);

        return back()->with('success', 'Component deployment script updated.');
    }
}
