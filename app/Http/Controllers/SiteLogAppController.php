<?php

namespace App\Http\Controllers;

use App\Facades\Toast;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteLogAppController extends Controller
{
    public function index(Server $server, Site $site): View
    {
        return view('site-logs-app.index', [
            'server' => $server,
            'site' => $site,
        ]);
    }

    public function getLog(Server $server, Site $site)
    {
        $data = $server->os()->readFile(data_get($site, 'type_data.log_app_path'));
        return empty($data) ? __('File: ":path" is empty.', ['path' => data_get($site, 'type_data.log_app_path')]) : $data;
    }

    public function updatePath(Server $server, Site $site, Request $request): RedirectResponse
    {
        $this->validate($request, [
            'log_app_path' => 'required|string',
        ]);

        try {
            $typeData = data_get($site, 'type_data');
            data_set($typeData, 'log_app_path', $request->input('log_app_path'));
            $site->type_data = $typeData;
            $site->save();

            Toast::success('Path updated successfully!');
        } catch (Throwable $e) {
            Toast::error($e->getMessage());
        }

        return back();
    }
}
