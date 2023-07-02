<?php

namespace App\Http\Controllers;

use App\Models\Server;

class CronjobController extends Controller
{
    public function index(Server $server)
    {
        return view('cronjobs.index', [
            'server' => $server,
        ]);
    }
}
