<?php

namespace App\Http\Controllers;

use App\Models\Server;

class ServiceController extends Controller
{
    public function index(Server $server)
    {
        return view('services.index', [
            'server' => $server,
        ]);
    }
}
