<?php

namespace App\Http\Controllers;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\DeleteDatabase;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('databases.index', [
            'server' => $server,
            'databases' => $server->databases,
            'databaseUsers' => $server->databaseUsers,
            'backups' => $server->backups,
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        $database = app(CreateDatabase::class)->create($server, $request->input());

        if ($request->input('user')) {
            app(CreateDatabaseUser::class)->create($server, $request->input(), [$database->name]);
        }

        Toast::success('Database created successfully.');

        return htmx()->back();
    }

    public function destroy(Server $server, Database $database): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(DeleteDatabase::class)->delete($server, $database);

        Toast::success('Database deleted successfully.');

        return back();
    }
}
