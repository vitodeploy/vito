<?php

namespace App\Http\Controllers\Applications;

use App\Actions\Component\CreateComponent;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\GetComponentOptions;
use App\Actions\Component\RetryComponentInstall;
use App\Http\Controllers\Controller;
use App\Http\Resources\ComponentResource;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Tables\Applications\ComponentTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}/components')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class ComponentController extends Controller
{
    #[Get('/', name: 'components.index')]
    public function index(Application $application, Environment $environment): Response
    {
        $this->authorize('viewAny', [Component::class, $environment, $application]);

        return Inertia::render('applications/environments/components', [
            'components' => ComponentTable::make($environment->components())->simplePaginate(),
            'options' => app(GetComponentOptions::class)->get($environment),
            'canCreate' => user()->can('create', [Component::class, $environment, $application]),
        ]);
    }

    #[Post('/', name: 'components.store')]
    public function store(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('create', [Component::class, $environment, $application]);

        $component = app(CreateComponent::class)->create($environment, $request->all());

        return to_route('components.show', [$application, $environment, $component])->with('success', 'Component installation queued.');
    }

    #[Get('/{component}', name: 'components.show')]
    public function show(Application $application, Environment $environment, Component $component): Response
    {
        $this->authorize('view', [$component, $environment, $application]);

        return Inertia::render('applications/environments/component', [
            'component' => new ComponentResource($component->load('server', 'capabilities')),
            'primaryCandidates' => app(GetComponentOptions::class)->primaryCandidates($environment),
            'can' => [
                'update' => user()->can('update', [$component, $environment, $application]),
                'delete' => user()->can('delete', [$component, $environment, $application]),
            ],
        ]);
    }

    #[Post('/{component}/retry', name: 'components.retry')]
    public function retry(Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('update', [$component, $environment, $application]);

        app(RetryComponentInstall::class)->retry($component);

        return back()->with('success', 'Component installation queued.');
    }

    #[Delete('/{component}', name: 'components.destroy')]
    public function destroy(Request $request, Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('delete', [$component, $environment, $application]);

        app(DeleteComponent::class)->delete($component, $request->all());

        return to_route('components.index', [$application, $environment])->with('success', 'Component deletion queued.');
    }
}
