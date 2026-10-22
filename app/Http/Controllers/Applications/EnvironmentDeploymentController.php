<?php

namespace App\Http\Controllers\Applications;

use App\Actions\EnvironmentDeployment\CancelEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\EnvironmentDeployment\RollbackEnvironment;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentScript;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentSettings;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnvironmentDeploymentResource;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Tables\Applications\EnvironmentDeploymentTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
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
    #[Get('/deployments', name: 'environment-deployments.index')]
    public function index(Application $application, Environment $environment): Response
    {
        $this->authorize('viewAny', [EnvironmentDeployment::class, $environment, $application]);

        return $this->deployments($environment, null);
    }

    #[Post('/deployments', name: 'environment-deployments.store')]
    public function store(Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('create', [EnvironmentDeployment::class, $environment, $application]);
        $deployment = app(DeployEnvironment::class)->run($environment, user());

        return to_route('environment-deployments.show', [$application, $environment, $deployment]);
    }

    #[Get('/deployments/{deployment}', name: 'environment-deployments.show')]
    public function show(Application $application, Environment $environment, EnvironmentDeployment $deployment): Response
    {
        $this->authorize('view', [$deployment, $environment, $application]);

        return $this->deployments($environment, new EnvironmentDeploymentResource($deployment->load('deployments.component.server', 'triggeredBy')));
    }

    #[Post('/deployments/{deployment}/cancel', name: 'environment-deployments.cancel')]
    public function cancel(Application $application, Environment $environment, EnvironmentDeployment $deployment): RedirectResponse
    {
        $this->authorize('update', [$deployment, $environment, $application]);
        app(CancelEnvironmentDeployment::class)->cancel($deployment);

        return back()->with('success', 'Deployment cancelled. In-flight work will finish before the next run starts.');
    }

    #[Post('/deployments/{deployment}/rollback', name: 'environment-deployments.rollback')]
    public function rollback(Application $application, Environment $environment, EnvironmentDeployment $deployment): RedirectResponse
    {
        $this->authorize('update', [$deployment, $environment, $application]);
        $run = app(RollbackEnvironment::class)->run($environment, $deployment, user());

        return to_route('environment-deployments.show', [$application, $environment, $run]);
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

    private function deployments(Environment $environment, ?EnvironmentDeploymentResource $deployment): Response
    {
        return Inertia::render('applications/environments/deployments', [
            'deployments' => EnvironmentDeploymentTable::make($environment->deployments())->simplePaginate(),
            'deployment' => $deployment,
        ]);
    }
}
