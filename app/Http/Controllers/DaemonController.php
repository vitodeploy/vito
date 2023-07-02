<?php

namespace App\Http\Controllers;

use App\Models\Server;

class DaemonController extends Controller
{
    public function index(Server $server)
    {
        return view('daemons.index', [
            'server' => $server,
        ]);
    }
}
