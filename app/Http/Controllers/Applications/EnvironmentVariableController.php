<?php

namespace App\Http\Controllers\Applications;

use App\Actions\EnvironmentVariable\DeleteEnvironmentVariable;
use App\Actions\EnvironmentVariable\GetEnvironmentVariables;
use App\Actions\EnvironmentVariable\ImportEnvironmentVariables;
use App\Actions\EnvironmentVariable\UpsertEnvironmentVariable;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnvironmentVariableResource;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
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

#[Prefix('applications/{application}/environments/{environment}/variables')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class EnvironmentVariableController extends Controller
{
    #[Get('/', name: 'environments.variables')]
    public function index(Application $application, Environment $environment): Response
    {
        $this->authorize('view', [$environment, $application]);

        return Inertia::render('applications/environments/variables', [
            'variables' => EnvironmentVariableResource::collection(app(GetEnvironmentVariables::class)->get($environment)),
            'warnings' => $application->preset()->warnings($environment),
            'canUpdate' => user()->can('update', [$environment, $application]),
        ]);
    }

    #[Post('/', name: 'environments.variables.store')]
    public function store(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);

        app(UpsertEnvironmentVariable::class)->upsert($environment, $request->all());

        return back()->with('success', 'Variable saved. Redeploy to apply.');
    }

    #[Put('/{variable}', name: 'environments.variables.update')]
    public function update(Request $request, Application $application, Environment $environment, EnvironmentVariable $variable): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);

        app(UpsertEnvironmentVariable::class)->upsert($environment, $request->all(), $variable);

        return back()->with('success', 'Variable saved. Redeploy to apply.');
    }

    #[Delete('/{variable}', name: 'environments.variables.destroy')]
    public function destroy(Application $application, Environment $environment, EnvironmentVariable $variable): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);

        app(DeleteEnvironmentVariable::class)->delete($environment, $variable);

        return back()->with('success', 'Variable deleted. Redeploy to apply.');
    }

    #[Post('/preview', name: 'environments.variables.preview')]
    public function preview(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);

        return back()->with('data', ['variables' => app(ImportEnvironmentVariables::class)->previewInput($request->all())]);
    }

    #[Post('/import', name: 'environments.variables.import')]
    public function import(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);

        app(ImportEnvironmentVariables::class)->import($environment, $request->all());

        return back()->with('success', 'Variables imported. Redeploy to apply.');
    }
}
