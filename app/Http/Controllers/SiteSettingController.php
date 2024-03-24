<?php

namespace App\Http\Controllers;

use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class SiteSettingController extends Controller
{
    public function index(Server $server, Site $site): View
    {
        return view('site-settings.index', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function getVhost(Server $server, Site $site): RedirectResponse
    {
        return back()->with('vhost', $server->webserver()->handler()->getVHost($site));
    }

    public function updateVhost(Server $server, Site $site, Request $request): RedirectResponse
    {
        $this->validate($request, [
            'vhost' => 'required|string',
        ]);

        try {
            $server->webserver()->handler()->updateVHost($site, false, $request->input('vhost'));

            Toast::success('VHost updated successfully!');
        } catch (Throwable $e) {
            Toast::error($e->getMessage());
        }

        return back();
    }

    public function updatePHPVersion(Server $server, Site $site, Request $request): HtmxResponse
    {
        $this->validate($request, [
            'version' => [
                'required',
                Rule::exists('services', 'version')->where('type', 'php'),
            ],
        ]);

        try {
            $site->changePHPVersion($request->input('version'));

            Toast::success('PHP version updated successfully!');
        } catch (Throwable $e) {
            Toast::error($e->getMessage());
        }

        return htmx()->back();
    }
}
