<?php

namespace App\Http\Livewire\Databases;

use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Server;
use App\Traits\HasCustomPaginationView;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DatabaseBackupFiles extends Component
{
    use HasCustomPaginationView;
    use RefreshComponentOnBroadcast;

    public Server $server;

    public Backup $backup;

    public string $restoreId = "";

    public string $restoreDatabaseId = "";

    public int $deleteId;

    public function backup(): void
    {
        $this->backup->run();

        $this->refreshComponent([]);
    }

    public function restore(): void
    {
        /** @var BackupFile $file */
        $file = BackupFile::query()->findOrFail($this->restoreId);

        /** @var Database $database */
        $database = Database::query()->findOrFail($this->restoreDatabaseId);

        $file->restore($database);

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('restored', true);
    }

    public function delete(): void
    {
        /** @var BackupFile $file */
        $file = BackupFile::query()->findOrFail($this->deleteId);

        $file->delete();

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function render(): View
    {
        return view('livewire.databases.database-backup-files', [
            'files' => $this->backup->files()->orderByDesc('id')->simplePaginate(10)
        ]);
    }
}
