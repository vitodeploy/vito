<?php

namespace App\Http\Controllers;

use App\Actions\Site\CreateSite;
use App\Actions\Site\DeleteSite;
use App\Enums\SiteStatus;
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
        $this->authorize('manage', $server);

        return view('sites.index', [
            'server' => $server,
            'sites' => $server->sites()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        $site = app(CreateSite::class)->create($server, $request->input());

        Toast::success('Site created');

        return htmx()->redirect(route('servers.sites.show', [$server, $site]));
    }

    public function create(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('sites.create', [
            'server' => $server,
            'type' => old('type', request()->query('type', SiteType::LARAVEL)),
            'sourceControls' => SourceControl::all(),
        ]);
    }

    public function show(Server $server, Site $site, Request $request): View|RedirectResponse|HtmxResponse
    {
        $this->authorize('manage', $server);

        if (in_array($site->status, [SiteStatus::INSTALLING, SiteStatus::INSTALLATION_FAILED])) {
            if ($request->hasHeader('HX-Request')) {
                return htmx()->redirect(route('servers.sites.installing', [$server, $site]));
            }

            return redirect()->route('servers.sites.installing', [$server, $site]);
        }

        return view('sites.show', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function installing(Server $server, Site $site, Request $request): View|RedirectResponse|HtmxResponse
    {
        $this->authorize('manage', $server);

        if (! in_array($site->status, [SiteStatus::INSTALLING, SiteStatus::INSTALLATION_FAILED])) {
            if ($request->hasHeader('HX-Request')) {
                return htmx()->redirect(route('servers.sites.show', [$server, $site]));
            }

            return redirect()->route('servers.sites.show', [$server, $site]);
        }

        return view('sites.installing', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function destroy(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(DeleteSite::class)->delete($site);

        Toast::success('Site is being deleted');

        return redirect()->route('servers.sites', $server);
    }
}
