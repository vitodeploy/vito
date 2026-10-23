<?php

namespace App\Http\Controllers\Applications;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
use App\Actions\CronJob\DisableCronJob;
use App\Actions\CronJob\EditCronJob;
use App\Actions\CronJob\EnableCronJob;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Component;
use App\Models\CronJob;
use App\Models\Environment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}/components/{component}/cronjobs')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class ComponentCronJobController extends Controller
{
    #[Post('/', name: 'components.cronjobs.store')]
    public function store(Request $request, Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('create', [CronJob::class, $component->server, null, $component, $environment, $application]);

        app(CreateCronJob::class)->create($component->server, $request->all(), component: $component);

        return back()->with('success', 'Cron job has been created.');
    }

    #[Put('/{cronJob}', name: 'components.cronjobs.update')]
    public function update(Request $request, Application $application, Environment $environment, Component $component, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('update', [$cronJob, $component->server, null, $component, $environment, $application]);

        app(EditCronJob::class)->edit($component->server, $cronJob, $request->all(), component: $component);

        return back()->with('success', 'Cron job has been updated.');
    }

    #[Post('/{cronJob}/enable', name: 'components.cronjobs.enable')]
    public function enable(Application $application, Environment $environment, Component $component, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('update', [$cronJob, $component->server, null, $component, $environment, $application]);

        app(EnableCronJob::class)->enable($component->server, $cronJob);

        return back()->with('success', 'Cron job has been enabled.');
    }

    #[Post('/{cronJob}/disable', name: 'components.cronjobs.disable')]
    public function disable(Application $application, Environment $environment, Component $component, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('update', [$cronJob, $component->server, null, $component, $environment, $application]);

        app(DisableCronJob::class)->disable($component->server, $cronJob);

        return back()->with('success', 'Cron job has been disabled.');
    }

    #[Delete('/{cronJob}', name: 'components.cronjobs.destroy')]
    public function destroy(Application $application, Environment $environment, Component $component, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('delete', [$cronJob, $component->server, null, $component, $environment, $application]);

        app(DeleteCronJob::class)->delete($component->server, $cronJob, $component);

        return back()->with('success', 'Cron job has been deleted.');
    }
}
