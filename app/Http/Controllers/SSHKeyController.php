<?php

namespace App\Http\Controllers;

use App\Models\Server;

class SSHKeyController extends Controller
{
    public function index(Server $server)
    {
        return view('server-ssh-keys.index', [
            'server' => $server,
        ]);
    }
}
