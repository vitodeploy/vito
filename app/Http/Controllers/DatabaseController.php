<?php

namespace App\Http\Controllers;

use App\Models\Server;

class DatabaseController extends Controller
{
    public function index(Server $server)
    {
        return view('databases.index', [
            'server' => $server,
        ]);
    }
}
