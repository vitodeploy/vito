<?php

namespace App\Http\Controllers\Settings;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Actions\ServerProvider\EditServerProvider;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\ServerProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServerProviderController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'providers' => ServerProvider::getByProjectId(auth()->user()->current_project_id)->get(),
        ];

        if ($request->has('edit')) {
            $data['editProvider'] = ServerProvider::find($request->input('edit'));
        }

        return view('settings.server-providers.index', $data);
    }

    public function connect(Request $request): HtmxResponse
    {
        app(CreateServerProvider::class)->create(
            $request->user(),
            $request->input()
        );

        Toast::success('Server provider connected.');

        return htmx()->redirect(route('settings.server-providers'));
    }

    public function update(ServerProvider $serverProvider, Request $request): HtmxResponse
    {
        app(EditServerProvider::class)->edit(
            $serverProvider,
            $request->user(),
            $request->input(),
        );

        Toast::success('Provider updated.');

        return htmx()->redirect(route('settings.server-providers'));
    }

    public function delete(ServerProvider $serverProvider): RedirectResponse
    {
        try {
            app(DeleteServerProvider::class)->delete($serverProvider);
        } catch (\Exception $e) {
            Toast::error($e->getMessage());

            return back();
        }

        Toast::success('Server provider deleted.');

        return back();
    }
}
