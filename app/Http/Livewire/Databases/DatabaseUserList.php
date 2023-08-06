<?php

namespace App\Http\Livewire\Databases;

use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DatabaseUserList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public int $deleteId;

    public string $username;

    public string $password;

    public bool $remote;

    public string $host = '%';

    public int $linkId;

    public array $link = [];

    public string $viewPassword = '';

    public function create(): void
    {
        app(CreateDatabaseUser::class)->create($this->server, $this->all());

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('database-user-created', true);
    }

    public function delete(): void
    {
        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::query()->findOrFail($this->deleteId);

        $databaseUser->deleteFromServer();

        $this->refreshComponent([]);

        $this->emitTo(DatabaseList::class, '$refresh');

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function viewPassword(int $id): void
    {
        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::query()->findOrFail($id);

        $this->viewPassword = $databaseUser->password;

        $this->dispatchBrowserEvent('open-modal', 'database-user-password');
    }

    public function showLink(int $id): void
    {
        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::query()->findOrFail($id);

        $this->linkId = $id;
        $this->link = $databaseUser->databases ?? [];

        $this->dispatchBrowserEvent('open-modal', 'link-database-user');
    }

    public function link(): void
    {
        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::query()->findOrFail($this->linkId);

        app(LinkUser::class)->link($databaseUser, $this->link);

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('linked', true);
    }

    public function render(): View
    {
        return view('livewire.databases.database-user-list', [
            'databases' => $this->server->databases,
            'databaseUsers' => $this->server->databaseUsers,
        ]);
    }
}
