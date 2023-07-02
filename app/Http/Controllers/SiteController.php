<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Contracts\View\View;

class SiteController extends Controller
{
    public function index(Server $server): View
    {
        return view('sites.index', [
            'server' => $server,
        ]);
    }

    public function create(Server $server): View
    {
        return view('sites.create', [
            'server' => $server,
        ]);
    }

    public function show(Server $server, Site $site): View
    {
        return view('sites.show', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function application(Server $server, Site $site): View
    {
        return view('sites.application', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function ssl(Server $server, Site $site): View
    {
        return view('sites.ssl', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function queues(Server $server, Site $site): View
    {
        return view('sites.queues', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function settings(Server $server, Site $site): View
    {
        return view('sites.settings', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function logs(Server $server, Site $site): View
    {
        return view('sites.logs', [
            'server' => $server,
            'site' => $site,
        ]);
    }
}
