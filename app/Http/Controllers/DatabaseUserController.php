<?php

namespace App\Http\Controllers;

use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\DeleteDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Actions\Database\SyncDatabaseUsers;
use App\Http\Resources\DatabaseResource;
use App\Http\Resources\DatabaseUserResource;
use App\Models\DatabaseUser;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('servers/{server}/database/users')]
#[Middleware(['auth', 'has-project'])]
class DatabaseUserController extends Controller
{
    #[Get('/', name: 'database-users')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [DatabaseUser::class, $server]);

        return Inertia::render('database-users/index', [
            'databases' => DatabaseResource::collection($server->databases()->get()),
            'databaseUsers' => DatabaseUserResource::collection($server->databaseUsers()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'database-users.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [DatabaseUser::class, $server]);

        app(CreateDatabaseUser::class)->create($server, $request->all());

        return back()
            ->with('success', 'Database user created successfully.');
    }

    #[Patch('/sync', name: 'database-users.sync')]
    public function sync(Server $server): RedirectResponse
    {
        $this->authorize('create', [DatabaseUser::class, $server]);

        app(SyncDatabaseUsers::class)->sync($server);

        return back()
            ->with('success', 'Database users synced successfully.');
    }

    #[Put('/link/{databaseUser}', name: 'database-users.link')]
    public function link(Request $request, Server $server, DatabaseUser $databaseUser): RedirectResponse
    {
        $this->authorize('update', [$databaseUser, $server]);

        app(LinkUser::class)->link($databaseUser, $request->all());

        return back()
            ->with('success', 'Database user permissions updated.');
    }

    #[Delete('/{databaseUser}', name: 'database-users.destroy')]
    public function destroy(Server $server, DatabaseUser $databaseUser): RedirectResponse
    {
        $this->authorize('delete', [$databaseUser, $server]);

        app(DeleteDatabaseUser::class)->delete($server, $databaseUser);

        return back()
            ->with('success', 'Database user deleted successfully.');
    }
}
