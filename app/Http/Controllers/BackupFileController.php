<?php

namespace App\Http\Controllers;

use App\Actions\Backup\RestoreBackup;
use App\Http\Resources\BackupFileResource;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/backups/{backup}/files')]
#[Middleware(['auth', 'has-project'])]
class BackupFileController extends Controller
{
    #[Get('/', name: 'backup-files')]
    public function index(Server $server, Backup $backup): Response
    {
        $this->authorize('viewAny', [BackupFile::class, $backup]);

        return Inertia::render('backups/files', [
            'backup' => BackupResource::make($backup),
            'files' => BackupFileResource::collection(
                $backup->files()->with('backup')->latest()->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Post('/{backupFile}/restore', name: 'backup-files.restore')]
    public function restore(Request $request, Server $server, Backup $backup, BackupFile $backupFile): RedirectResponse
    {
        $this->authorize('update', $backup);

        app(RestoreBackup::class)->restore($backupFile, $request->input());

        return back()
            ->with('info', 'Backup is being restored...');
    }

    #[Delete('/{backupFile}', name: 'backup-files.destroy')]
    public function destroy(Server $server, Backup $backup, BackupFile $backupFile): RedirectResponse
    {
        $this->authorize('delete', $backupFile);

        $backupFile->deleteFile();

        return back()
            ->with('success', 'File deleted successfully.');
    }
}
