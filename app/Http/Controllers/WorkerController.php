<?php

namespace App\Http\Controllers;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\DeleteWorker;
use App\Actions\Worker\EditWorker;
use App\Actions\Worker\GetWorkerLogs;
use App\Actions\Worker\ManageWorker;
use App\Http\Resources\WorkerResource;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
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

#[Prefix('servers/{server}')]
#[Middleware(['auth', 'has-project'])]
class WorkerController extends Controller
{
    #[Get('/workers', name: 'workers')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Worker::class, $server]);

        return Inertia::render('workers/index', [
            'workers' => WorkerResource::collection(
                $server->workers()->latest()->simplePaginate(config('web.pagination_size'))
            ),
            'sites' => $server->sites()->select('id', 'domain')->get(),
        ]);
    }

    #[Get('/sites/{site}/workers', name: 'workers.site')]
    public function site(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [Worker::class, $server, $site]);

        return Inertia::render('workers/index', [
            'workers' => WorkerResource::collection(
                $site->workers()->latest()->simplePaginate(config('web.pagination_size'))
            ),
            'sites' => $server->sites()->select('id', 'domain')->get(),
        ]);
    }

    #[Post('/workers/{site?}', name: 'workers.store')]
    public function store(Request $request, Server $server, ?Site $site = null): RedirectResponse
    {
        $this->authorize('create', [Worker::class, $server, $site]);

        app(CreateWorker::class)->create($server, $request->all(), $site);

        return back()
            ->with('info', 'Worker is being created.');
    }

    #[Put('/workers/{worker}/{site?}', name: 'workers.update')]
    public function update(Request $request, Server $server, Worker $worker, ?Site $site = null): RedirectResponse
    {
        $this->authorize('update', [$worker, $server, $site]);

        app(EditWorker::class)->edit($worker, $request->all());

        return back()
            ->with('info', 'Worker is being updated.');
    }

    #[Post('/workers/{worker}/start', name: 'workers.start')]
    public function start(Server $server, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $server]);

        app(ManageWorker::class)->start($worker);

        return back()
            ->with('info', 'Worker is being started.');
    }

    #[Post('/workers/{worker}/stop', name: 'workers.stop')]
    public function stop(Server $server, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $server]);

        app(ManageWorker::class)->stop($worker);

        return back()
            ->with('info', 'Worker is being stopped.');
    }

    #[Post('/workers/{worker}/restart', name: 'workers.restart')]
    public function restart(Server $server, Worker $worker): RedirectResponse
    {
        $this->authorize('update', [$worker, $server]);

        app(ManageWorker::class)->restart($worker);

        return back()
            ->with('info', 'Worker is being restarted.');
    }

    #[Get('/workers/{worker}/logs', name: 'workers.logs')]
    public function logs(Server $server, Worker $worker): JsonResponse
    {
        $this->authorize('view', [$worker, $server]);

        $logs = app(GetWorkerLogs::class)->getLogs($worker);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    #[Delete('/{worker}/{site?}', name: 'workers.destroy')]
    public function destroy(Server $server, Worker $worker, ?Site $site = null): RedirectResponse
    {
        $this->authorize('delete', [$worker, $server, $site]);

        app(DeleteWorker::class)->delete($worker);

        return back()
            ->with('info', 'Worker is being deleted.');
    }
}
