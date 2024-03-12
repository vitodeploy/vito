<?php

namespace App\Http\Controllers;

use App\Actions\Database\CreateBackup;
use App\Actions\Database\RestoreBackup;
use App\Actions\Database\RunBackup;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatabaseBackupController extends Controller
{
    public function show(Server $server, Backup $backup): View
    {
        return view('databases.backups', [
            'server' => $server,
            'databases' => $server->databases,
            'backup' => $backup,
            'files' => $backup->files()->orderByDesc('id')->simplePaginate(10),
        ]);
    }

    public function run(Server $server, Backup $backup): RedirectResponse
    {
        app(RunBackup::class)->run($backup);

        Toast::success('Backup is running.');

        return back();
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        app(CreateBackup::class)->create('database', $server, $request->input());

        Toast::success('Backup created successfully.');

        return htmx()->back();
    }

    public function destroy(Server $server, Backup $backup): RedirectResponse
    {
        $backup->delete();

        Toast::success('Backup deleted successfully.');

        return back();
    }

    public function restore(Server $server, Backup $backup, BackupFile $backupFile, Request $request): HtmxResponse
    {
        app(RestoreBackup::class)->restore($backupFile, $request->input());

        Toast::success('Backup restored successfully.');

        return htmx()->back();
    }

    public function destroyFile(Server $server, Backup $backup, BackupFile $backupFile): RedirectResponse
    {
        $backupFile->delete();

        Toast::success('Backup file deleted successfully.');

        return back();
    }
}
