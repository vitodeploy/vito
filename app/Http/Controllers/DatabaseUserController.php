<?php

namespace App\Http\Controllers;

use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\DeleteDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\DatabaseUser;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatabaseUserController extends Controller
{
    public function store(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        $database = app(CreateDatabaseUser::class)->create($server, $request->input());

        if ($request->input('user')) {
            app(CreateDatabaseUser::class)->create($server, $request->input(), [$database->name]);
        }

        Toast::success('User created successfully.');

        return htmx()->back();
    }

    public function destroy(Server $server, DatabaseUser $databaseUser): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(DeleteDatabaseUser::class)->delete($server, $databaseUser);

        Toast::success('User deleted successfully.');

        return back();
    }

    public function password(Server $server, DatabaseUser $databaseUser): RedirectResponse
    {
        $this->authorize('manage', $server);

        return back()->with([
            'password' => $databaseUser->password,
        ]);
    }

    public function link(Server $server, DatabaseUser $databaseUser, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        app(LinkUser::class)->link($databaseUser, $request->input());

        Toast::success('Database linked successfully.');

        return htmx()->back();
    }
}
