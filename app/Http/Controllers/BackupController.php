<?php

namespace App\Http\Controllers;

use App\Actions\Database\ManageBackup;
use App\Actions\Database\RunBackup;
use App\Http\Resources\BackupFileResource;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
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

#[Prefix('servers/{server}/database/backups')]
#[Middleware(['auth', 'has-project'])]
class BackupController extends Controller
{
    #[Get('/', name: 'backups')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Backup::class, $server]);

        return Inertia::render('backups/index', [
            'backups' => BackupResource::collection(
                $server->backups()->with('lastFile')->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Get('/{backup}', name: 'backups.show')]
    public function show(Server $server, Backup $backup): JsonResponse
    {
        $this->authorize('view', $backup);

        return response()->json([
            'backup' => BackupResource::make($backup),
            'files' => BackupFileResource::collection($backup->files()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'backups.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [Backup::class, $server]);

        app(ManageBackup::class)->create($server, $request->all());

        return back()
            ->with('info', 'Backup is being created...');
    }

    #[Patch('/{backup}', name: 'backups.update')]
    public function update(Request $request, Server $server, Backup $backup): RedirectResponse
    {
        $this->authorize('update', $backup);

        app(ManageBackup::class)->update($backup, $request->all());

        return back()
            ->with('success', 'Backup updated successfully.');
    }

    #[Post('/{backup}/run', name: 'backups.run')]
    public function run(Server $server, Backup $backup): RedirectResponse
    {
        $this->authorize('create', [BackupFile::class, $backup]);

        app(RunBackup::class)->run($backup);

        return back()
            ->with('info', 'Backup is being created...');
    }

    #[Delete('/{backup}', name: 'backups.destroy')]
    public function destroy(Server $server, Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);

        app(ManageBackup::class)->delete($backup);

        return back()
            ->with('warning', 'Backup is being deleted...');
    }
}
