<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Server;
use Illuminate\Contracts\View\View;

class DatabaseController extends Controller
{
    public function index(Server $server): View
    {
        return view('databases.index', [
            'server' => $server,
        ]);
    }

    public function backups(Server $server, Backup $backup): View
    {
        return view('databases.backups', [
            'server' => $server,
            'backup' => $backup,
        ]);
    }
}
