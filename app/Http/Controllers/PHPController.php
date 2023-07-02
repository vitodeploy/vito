<?php

namespace App\Http\Controllers;

use App\Models\Server;

class PHPController extends Controller
{
    public function index(Server $server)
    {
        return view('php.index', [
            'server' => $server,
        ]);
    }
}
