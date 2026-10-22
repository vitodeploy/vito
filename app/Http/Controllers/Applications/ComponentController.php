<?php

namespace App\Http\Controllers\Applications;

use App\Actions\Component\CreateComponent;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\RetryComponentInstall;
use App\Actions\Component\UpdateComponentCapabilities;
use App\Actions\Component\UpdateLoadBalancerBackends;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}/components')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class ComponentController extends Controller
{
    #[Post('/', name: 'components.store')]
    public function store(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('create', [Component::class, $environment, $application]);

        $component = app(CreateComponent::class)->create($environment, $request->all());

        return to_route('environments.show', [$application, $environment, 'component' => $component->id])->with('success', 'Component installation queued.');
    }

    #[Get('/{component}', name: 'components.show')]
    public function show(Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('view', [$component, $environment, $application]);

        return to_route('environments.show', [$application, $environment, 'component' => $component->id]);
    }

    #[Put('/{component}/capabilities', name: 'components.capabilities.update')]
    public function capabilities(Request $request, Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('update', [$component, $environment, $application]);

        app(UpdateComponentCapabilities::class)->update($component, $request->all());

        return back()->with('success', 'Capability changes queued.');
    }

    #[Put('/{component}/backends', name: 'components.backends.update')]
    public function backends(Request $request, Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('update', [$component, $environment, $application]);

        app(UpdateLoadBalancerBackends::class)->update($component, $request->all());

        return back()->with('success', 'Load balancer changes queued.');
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

        return to_route('environments.show', [$application, $environment])->with('success', 'Component deletion queued.');
    }
}
