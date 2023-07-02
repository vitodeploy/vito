<?php

namespace App\Http\Controllers;

use App\Models\Server;

class FirewallController extends Controller
{
    public function index(Server $server)
    {
        return view('firewall.index', [
            'server' => $server,
        ]);
    }
}
