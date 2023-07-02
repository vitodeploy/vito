<?php

namespace App\Http\Controllers;

use App\Models\Server;

class ServerController extends Controller
{
    public function index()
    {
        return view('servers.index');
    }

    public function create()
    {
        return view('servers.create');
    }

    public function show(Server $server)
    {
        return view('servers.show', compact('server'));
    }

    public function logs(Server $server)
    {
        return view('servers.logs', compact('server'));
    }
}
