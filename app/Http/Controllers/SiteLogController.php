<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Contracts\View\View;

class SiteLogController extends Controller
{
    public function index(Server $server, Site $site): View
    {
        $this->authorize('manage', $server);

        return view('site-logs.index', [
            'server' => $server,
            'site' => $site,
            'pageTitle' => __('Vito Logs'),
        ]);
    }
}
