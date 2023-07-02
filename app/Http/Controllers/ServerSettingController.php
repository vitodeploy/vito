<?php

namespace App\Http\Controllers;

use App\Models\Server;

class ServerSettingController extends Controller
{
    public function index(Server $server)
    {
        return view('server-settings.index', compact('server'));
    }
}
