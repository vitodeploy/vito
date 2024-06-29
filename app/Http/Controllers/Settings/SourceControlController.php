<?php

namespace App\Http\Controllers\Settings;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Actions\SourceControl\DeleteSourceControl;
use App\Actions\SourceControl\EditSourceControl;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\SourceControl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SourceControlController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'sourceControls' => SourceControl::getByProjectId(auth()->user()->current_project_id)->get(),
        ];

        if ($request->has('edit')) {
            $data['editSourceControl'] = SourceControl::find($request->input('edit'));
        }

        return view('settings.source-controls.index', $data);
    }

    public function connect(Request $request): HtmxResponse
    {
        app(ConnectSourceControl::class)->connect(
            $request->user(),
            $request->input(),
        );

        Toast::success('Source control connected.');

        return htmx()->redirect(route('settings.source-controls'));
    }

    public function update(SourceControl $sourceControl, Request $request): HtmxResponse
    {
        app(EditSourceControl::class)->edit(
            $sourceControl,
            $request->user(),
            $request->input(),
        );

        Toast::success('Source control updated.');

        return htmx()->redirect(route('settings.source-controls'));
    }

    public function delete(SourceControl $sourceControl): RedirectResponse
    {
        try {
            app(DeleteSourceControl::class)->delete($sourceControl);
        } catch (\Exception $e) {
            Toast::error($e->getMessage());

            return back();
        }

        Toast::success('Source control deleted.');

        return redirect()->route('settings.source-controls');
    }
}
