<?php

namespace App\Http\Controllers\Applications;

use App\Actions\EnvironmentDomain\CreateEnvironmentDomain;
use App\Actions\EnvironmentDomain\DeleteEnvironmentDomain;
use App\Actions\EnvironmentDomain\RequestEnvironmentDomainVerification;
use App\Actions\EnvironmentDomain\UpdateEnvironmentDomain;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}/domains')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class EnvironmentDomainController extends Controller
{
    #[Post('/', name: 'environments.domains.store')]
    public function store(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('create', [EnvironmentDomain::class, $environment, $application]);
        app(CreateEnvironmentDomain::class)->create($environment, $request->all());

        return back()->with('success', 'Domain added. Verification is queued.');
    }

    #[Put('/{domain}', name: 'environments.domains.update')]
    public function update(Request $request, Application $application, Environment $environment, EnvironmentDomain $domain): RedirectResponse
    {
        $this->authorize('update', [$domain, $environment, $application]);
        app(UpdateEnvironmentDomain::class)->update($domain, $request->all());

        return back()->with('success', 'Domain updated. Host and certificate changes require verification.');
    }

    #[Post('/{domain}/verify', name: 'environments.domains.verify')]
    public function verify(Application $application, Environment $environment, EnvironmentDomain $domain): RedirectResponse
    {
        $this->authorize('update', [$domain, $environment, $application]);
        app(RequestEnvironmentDomainVerification::class)->verify($domain);

        return back()->with('success', 'Domain verification queued.');
    }

    #[Delete('/{domain}', name: 'environments.domains.destroy')]
    public function destroy(Application $application, Environment $environment, EnvironmentDomain $domain): RedirectResponse
    {
        $this->authorize('delete', [$domain, $environment, $application]);
        app(DeleteEnvironmentDomain::class)->delete($domain);

        return back()->with('success', 'Domain removed.');
    }
}
