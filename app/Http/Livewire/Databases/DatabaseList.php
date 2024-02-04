<?php

namespace App\Http\Livewire\Databases;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Models\Database;
use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DatabaseList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public int $deleteId;

    public string $name;

    public bool $user;

    public string $username;

    public string $password;

    public bool $remote = false;

    public string $host = '%';

    public function create(): void
    {
        $database = app(CreateDatabase::class)->create($this->server, $this->all());

        if ($this->all()['user']) {
            app(CreateDatabaseUser::class)->create($this->server, $this->all(), [$database->name]);
        }

        $this->refreshComponent([]);

        $this->dispatch('database-created');
    }

    public function delete(): void
    {
        /** @var Database $database */
        $database = Database::query()->findOrFail($this->deleteId);

        $database->deleteFromServer();

        $this->refreshComponent([]);

        $this->dispatch('$refresh')->to(DatabaseUserList::class);

        $this->dispatch('confirmed');
    }

    public function render(): View
    {
        return view('livewire.databases.database-list', [
            'databases' => $this->server->databases,
        ]);
    }
}
