<?php

namespace App\Http\Controllers\Settings;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Actions\SourceControl\DeleteSourceControl;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\SourceControl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SourceControlController extends Controller
{
    public function index(): View
    {
        return view('settings.source-controls.index', [
            'sourceControls' => SourceControl::query()->orderByDesc('id')->get(),
        ]);
    }

    public function connect(Request $request): HtmxResponse
    {
        app(ConnectSourceControl::class)->connect(
            $request->input(),
        );

        Toast::success('Source control connected.');

        return htmx()->redirect(route('source-controls'));
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

        return redirect()->route('source-controls');
    }
}
