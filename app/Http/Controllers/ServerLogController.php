<?php

namespace App\Http\Controllers;

use App\Actions\ServerLog\CreateLog;
use App\Actions\ServerLog\UpdateLog;
use App\Helpers\QueryBuilder;
use App\Http\Resources\ServerLogResource;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Prefix('servers/{server}/logs')]
#[Middleware(['auth', 'has-project'])]
class ServerLogController extends Controller
{
    #[Get('/', name: 'logs')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [ServerLog::class, $server]);

        $logs = QueryBuilder::for($server->logs()->where('is_remote', 0))
            ->searchableFields(['name'])
            ->sortable('created_at', 'desc')
            ->query()
            ->simplePaginate(config('web.pagination_size'));

        return Inertia::render('server-logs/index', [
            'title' => 'Server logs',
            'logs' => ServerLogResource::collection($logs),
        ]);
    }

    #[Get('/remote', name: 'logs.remote')]
    public function remote(Server $server): Response
    {
        $this->authorize('viewAny', [ServerLog::class, $server]);

        return Inertia::render('server-logs/index', [
            'title' => 'Remote logs',
            'logs' => ServerLogResource::collection($server->logs()->where('is_remote', 1)->latest()->simplePaginate(config('web.pagination_size'))),
            'remote' => true,
        ]);
    }

    #[Get('/json/{site?}', name: 'logs.json')]
    public function json(Request $request, Server $server, ?Site $site = null): ResourceCollection
    {
        $this->authorize('viewAny', [ServerLog::class, $server]);

        $logs = $server->logs()
            ->when($site, fn ($query) => $query->where('site_id', $site->id))
            ->latest()
            ->simplePaginate($request->query('count') ?? config('web.pagination_size'));

        return ServerLogResource::collection($logs);
    }

    #[Get('/{log}', name: 'logs.show')]
    public function show(Server $server, ServerLog $log): string
    {
        $this->authorize('view', $log);

        return $log->getContent();
    }

    /**
     * @throws Throwable
     */
    #[Get('/{log}/download', name: 'logs.download')]
    public function download(Server $server, ServerLog $log): StreamedResponse
    {
        $this->authorize('view', $log);

        return $log->download();
    }

    #[Post('/', name: 'logs.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [ServerLog::class, $server]);

        app(CreateLog::class)->create($server, $request->input());

        return back()->with('success', 'Log created successfully');
    }

    #[Patch('{log}', name: 'logs.update')]
    public function update(Request $request, Server $server, ServerLog $log): RedirectResponse
    {
        $this->authorize('update', $log);

        app(UpdateLog::class)->update($log, $request->input());

        return back()->with('success', 'Log updated successfully');
    }

    #[Delete('{log}', name: 'logs.destroy')]
    public function destroy(Server $server, ServerLog $log): RedirectResponse
    {
        $this->authorize('delete', $log);

        $log->delete();

        return back()->with('success', 'Log deleted successfully');
    }

    #[Post('{log}/clear', name: 'logs.clear')]
    public function clear(Server $server, ServerLog $log): RedirectResponse
    {
        $this->authorize('update', $log);

        $log->clear();

        return back()->with('success', 'Log cleared successfully');
    }
}
