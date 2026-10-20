<?php

namespace App\Http\Controllers\Applications;

use App\Actions\Component\GetComponentOptions;
use App\Actions\Environment\CreateEnvironment;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\Environment\GetEnvironmentTopology;
use App\Actions\Environment\UpdateEnvironment;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnvironmentDeploymentResource;
use App\Models\Application;
use App\Models\Environment;
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
    public function show(Application $application, Environment $environment): Response
    {
        $this->authorize('view', [$environment, $application]);

        return Inertia::render('applications/environments/show', [
            'primaryCandidates' => fn () => app(GetComponentOptions::class)->primaryCandidates($environment),
            'topology' => fn () => app(GetEnvironmentTopology::class)->get($environment),
            'lastDeployment' => fn () => ($run = $environment->deployments()->latest('id')->first())
                ? new EnvironmentDeploymentResource($run) : null,
            'warnings' => fn () => $application->preset()->warnings($environment),
            'can' => [
                'update' => user()->can('update', [$environment, $application]),
                'delete' => user()->can('delete', [$environment, $application]),
            ],
        ]);
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
}
