<?php

namespace App\Http\Controllers;

use App\Actions\Site\CreateSite;
use App\Actions\Site\DeleteSite;
use App\Enums\SiteType;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Server $server): View
    {
        return view('sites.index', [
            'server' => $server,
            'sites' => $server->sites()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        $site = app(CreateSite::class)->create($server, $request->input());

        Toast::success('Site created');

        return htmx()->redirect(route('servers.sites.show', [$server, $site]));
    }

    public function create(Server $server): View
    {
        return view('sites.create', [
            'server' => $server,
            'type' => old('type', request()->query('type', SiteType::LARAVEL)),
            'sourceControls' => SourceControl::all(),
        ]);
    }

    public function show(Server $server, Site $site): View
    {
        return view('sites.show', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function destroy(Server $server, Site $site): RedirectResponse
    {
        app(DeleteSite::class)->delete($site);

        Toast::success('Site is being deleted');

        return redirect()->route('servers.sites', $server);
    }
}
