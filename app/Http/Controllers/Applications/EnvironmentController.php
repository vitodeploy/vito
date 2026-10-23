<?php

namespace App\Http\Controllers\Applications;

use App\Actions\Component\GetComponentOptions;
use App\Actions\Environment\CreateEnvironment;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\Environment\GetEnvironmentOverview;
use App\Actions\Environment\UpdateEnvironment;
use App\Actions\EnvironmentAttachment\GetEnvironmentResourceOptions;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
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
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class EnvironmentController extends Controller
{
    #[Post('/', name: 'environments.store')]
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('create', [Environment::class, $application]);

        $environment = app(CreateEnvironment::class)->create($application, $request->all());

        return to_route('environments.show', [$application, $environment])->with('success', 'Environment created.');
    }

    #[Get('/{environment}', name: 'environments.show')]
    public function show(Request $request, Application $application, Environment $environment): Response
    {
        $this->authorize('view', [$environment, $application]);

        return $this->render($request, $application, $environment);
    }

    #[Get('/{environment}/settings', name: 'environments.settings')]
    public function settings(Request $request, Application $application, Environment $environment): Response
    {
        $this->authorize('view', [$environment, $application]);

        return $this->render($request, $application, $environment, 'settings');
    }

    #[Get('/{environment}/variables', name: 'environments.variables')]
    public function variables(Request $request, Application $application, Environment $environment): Response
    {
        $this->authorize('view', [$environment, $application]);

        return $this->render($request, $application, $environment, 'variables');
    }

    #[Get('/{environment}/deployments', name: 'environment-deployments.index')]
    public function deployments(Request $request, Application $application, Environment $environment): Response
    {
        $this->authorize('viewAny', [EnvironmentDeployment::class, $environment, $application]);

        return $this->render($request, $application, $environment, 'deployments');
    }

    #[Get('/{environment}/deployments/{deployment}', name: 'environment-deployments.show')]
    public function deployment(Request $request, Application $application, Environment $environment, EnvironmentDeployment $deployment): Response
    {
        $this->authorize('view', [$deployment, $environment, $application]);

        return $this->render($request, $application, $environment, 'deployments', $deployment);
    }

    #[Put('/{environment}', name: 'environments.update')]
    public function update(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);

        app(UpdateEnvironment::class)->update($environment, $request->all());

        return back()->with('success', 'Environment updated. Changes apply on the next deploy.');
    }

    #[Delete('/{environment}', name: 'environments.destroy')]
    public function destroy(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('delete', [$environment, $application]);

        app(DeleteEnvironment::class)->delete($environment, $request->all());

        return to_route('applications.show', $application)->with('success', 'Environment deletion requested. Components are cleaned up before the environment is removed.');
    }

    /**
     * Every environment URL renders the graph; sheet routes add the data for the sheet they open on top of it.
     */
    private function render(Request $request, Application $application, Environment $environment, ?string $sheet = null, ?EnvironmentDeployment $deployment = null): Response
    {
        $overview = app(GetEnvironmentOverview::class);
        $component = $overview->component($environment, $request->integer('component') ?: null);
        $attachment = $overview->attachment($environment, $request->integer('resource') ?: null);
        if ($component) {
            $this->authorize('view', [$component, $environment, $application]);
        }
        $canCreateComponent = user()->can('create', [Component::class, $environment, $application]);
        $canUpdate = user()->can('update', [$environment, $application]);

        return Inertia::render('applications/environments/show', [
            ...$overview->props($environment, $component, $attachment),
            'options' => Inertia::optional(fn () => $canCreateComponent ? app(GetComponentOptions::class)->get($environment) : null),
            'resourceOptions' => Inertia::optional(fn () => $canUpdate ? app(GetEnvironmentResourceOptions::class)->get($environment) : null),
            ...$overview->sheetProps($environment, user(), $sheet, $deployment),
            'can' => fn () => [
                'update' => $canUpdate,
                'delete' => user()->can('delete', [$environment, $application]),
                'updateApplication' => user()->can('update', $application),
                'deleteApplication' => user()->can('delete', $application),
                'createComponent' => $canCreateComponent,
                'updateComponent' => $component && user()->can('update', [$component, $environment, $application]),
                'deleteComponent' => $component && user()->can('delete', [$component, $environment, $application]),
            ],
        ]);
    }
}
