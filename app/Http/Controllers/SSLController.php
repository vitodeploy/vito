<?php

namespace App\Http\Controllers;

use App\Actions\SSL\CreateSSL;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SSLController extends Controller
{
    public function index(Server $server, Site $site): View
    {
        return view('ssls.index', [
            'server' => $server,
            'site' => $site,
            'ssls' => $site->ssls,
        ]);
    }

    public function store(Server $server, Site $site, Request $request): HtmxResponse
    {
        app(CreateSSL::class)->create($site, $request->input());

        Toast::success('SSL certificate is being created.');

        return htmx()->back();
    }

    public function destroy(Server $server, Site $site, Ssl $ssl): RedirectResponse
    {
        $ssl->remove();

        Toast::success('SSL certificate is being deleted.');

        return back();
    }
}
