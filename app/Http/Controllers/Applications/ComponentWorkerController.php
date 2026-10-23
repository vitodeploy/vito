<?php

namespace App\Http\Controllers\Applications;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\DeleteWorker;
use App\Actions\Worker\EditWorker;
use App\Actions\Worker\ManageWorker;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}/components/{component}/workers')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class ComponentWorkerController extends Controller
{
    #[Post('/', name: 'components.workers.store')]
    public function store(Request $request, Application $application, Environment $environment, Component $component): RedirectResponse
    {
        $this->authorize('create', [Worker::class, $component->server, null, $component, $environment, $application]);

        app(CreateWorker::class)->create($component->server, $request->all(), component: $component);

        return back()->with('info', 'Worker is being created.');
    }

    #[Put('/{worker}', name: 'components.workers.update')]
    public function update(Request $request, Application $application, Environment $environment, Component $component, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $component->server, null, $component, $environment, $application]);

        app(EditWorker::class)->edit($worker, $request->all(), $component);

        return back()->with('info', 'Worker is being updated.');
    }

    #[Post('/{worker}/start', name: 'components.workers.start')]
    public function start(Application $application, Environment $environment, Component $component, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $component->server, null, $component, $environment, $application]);

        app(ManageWorker::class)->start($worker);

        return back()->with('info', 'Worker is being started.');
    }

    #[Post('/{worker}/stop', name: 'components.workers.stop')]
    public function stop(Application $application, Environment $environment, Component $component, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $component->server, null, $component, $environment, $application]);

        app(ManageWorker::class)->stop($worker);

        return back()->with('info', 'Worker is being stopped.');
    }

    #[Post('/{worker}/restart', name: 'components.workers.restart')]
    public function restart(Application $application, Environment $environment, Component $component, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $component->server, null, $component, $environment, $application]);

        app(ManageWorker::class)->restart($worker);

        return back()->with('info', 'Worker is being restarted.');
    }

    #[Delete('/{worker}', name: 'components.workers.destroy')]
    public function destroy(Application $application, Environment $environment, Component $component, Worker $worker): RedirectResponse
    {
        $this->authorize('delete', [$worker, $component->server, null, $component, $environment, $application]);

        app(DeleteWorker::class)->delete($worker, $component);

        return back()->with('info', 'Worker has been deleted.');
    }
}
