<?php

namespace App\Http\Controllers;

use App\Actions\Site\UpdateSourceControl;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;
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
        /** @var Webserver $handler */
        $handler = $server->webserver()->handler();

        return back()->with('vhost', $handler->getVHost($site));
    }

    public function updateVhost(Server $server, Site $site, Request $request): RedirectResponse
    {
        $this->validate($request, [
            'vhost' => 'required|string',
        ]);

        try {
            /** @var Webserver $handler */
            $handler = $server->webserver()->handler();
            $handler->updateVHost($site, false, $request->input('vhost'));

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

    public function updateSourceControl(Server $server, Site $site, Request $request): HtmxResponse
    {
        $site = app(UpdateSourceControl::class)->update($site, $request->input());

        Toast::success('Source control updated successfully!');

        return htmx()->back();
    }
}
