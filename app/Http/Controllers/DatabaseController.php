<?php

namespace App\Http\Controllers;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\DeleteDatabase;
use App\Actions\Database\SyncDatabases;
use App\Http\Resources\DatabaseResource;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
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

#[Prefix('servers/{server}/database')]
#[Middleware(['auth', 'has-project'])]
class DatabaseController extends Controller
{
    #[Get('/', name: 'databases')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Database::class, $server]);

        return Inertia::render('databases/index', [
            'databases' => DatabaseResource::collection($server->databases()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'databases.json')]
    public function json(Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Database::class, $server]);

        return DatabaseResource::collection($server->databases()->get());
    }

    #[Get('/charsets', name: 'databases.charsets')]
    public function charsets(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $charsets = [];
        foreach ($server->database()->type_data['charsets'] as $charset => $value) {
            $charsets[] = $charset;
        }

        return response()->json($charsets);
    }

    #[Get('/collations/{charset?}', name: 'databases.collations')]
    public function collations(Server $server, ?string $charset = null): JsonResponse
    {
        $this->authorize('view', $server);

        if (! $charset) {
            $charset = $server->database()->type_data['defaultCharset'] ?? null;
        }

        $charsets = $server->database()->type_data['charsets'] ?? [];

        return response()->json(data_get($charsets, $charset.'.list', data_get($charsets, $charset.'.default', [])));
    }

    #[Post('/', name: 'databases.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [Database::class, $server]);

        app(CreateDatabase::class)->create($server, $request->all());

        return back()
            ->with('success', 'Database created successfully.');
    }

    #[Patch('/sync', name: 'databases.sync')]
    public function sync(Server $server): RedirectResponse
    {
        $this->authorize('create', [Database::class, $server]);

        app(SyncDatabases::class)->sync($server);

        return back()
            ->with('success', 'Databases synced successfully.');
    }

    #[Delete('/{database}', name: 'databases.destroy')]
    public function destroy(Server $server, Database $database): RedirectResponse
    {
        $this->authorize('delete', [$database, $server]);

        app(DeleteDatabase::class)->delete($server, $database);

        return back()
            ->with('success', 'Database deleted successfully.');
    }
}
