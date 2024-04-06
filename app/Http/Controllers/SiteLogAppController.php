<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteLogAppController extends Controller
{
    public function index(Server $server, Site $site): View
    {
        return view('site-logs-app.index', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function getLog(Server $server, Site $site): RedirectResponse
    {
        return back()->with('vhost', $server->webserver()->handler()->getVHost($site));
    }
}
