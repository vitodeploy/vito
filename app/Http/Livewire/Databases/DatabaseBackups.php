<?php

namespace App\Http\Livewire\Databases;

use App\Actions\Database\CreateBackup;
use App\Models\Backup;
use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class DatabaseBackups extends Component
{
    use RefreshComponentOnBroadcast;
    use WithPagination;

    public Server $server;

    public int $deleteId;

    public string $database = '';

    public string $storage = '';

    public string $interval = '';

    public string $custom = '';

    public int $keep = 10;

    public ?Backup $backup = null;

    protected ?Paginator $files = null;

    public function create(): void
    {
        app(CreateBackup::class)->create('database', $this->server, $this->all());

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('backup-created', true);
    }

    public function files(int $id): void
    {
        $backup = Backup::query()->findOrFail($id);
        $this->backup = $backup;
        $this->files = $backup->files()->orderByDesc('id')->simplePaginate(1);
        $this->dispatchBrowserEvent('show-files', true);
    }

    public function backup(): void
    {
        $this->backup?->run();

        $this->files = $this->backup?->files()->orderByDesc('id')->simplePaginate();

        $this->dispatchBrowserEvent('backup-running', true);
    }

    public function delete(): void
    {
        /** @var Backup $backup */
        $backup = Backup::query()->findOrFail($this->deleteId);

        $backup->delete();

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function render(): View
    {
        return view('livewire.databases.database-backups', [
            'backups' => $this->server->backups,
            'databases' => $this->server->databases,
            'files' => $this->files,
        ]);
    }
}
