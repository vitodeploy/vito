<?php

namespace App\Http\Controllers;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
use App\Actions\CronJob\DisableCronJob;
use App\Actions\CronJob\EnableCronJob;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\CronJob;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CronjobController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('cronjobs.index', [
            'server' => $server,
            'cronjobs' => $server->cronJobs,
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        app(CreateCronJob::class)->create($server, $request->input());

        Toast::success('Cronjob created successfully.');

        return htmx()->back();
    }

    public function destroy(Server $server, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(DeleteCronJob::class)->delete($server, $cronJob);

        Toast::success('Cronjob deleted successfully.');

        return back();
    }

    public function enable(Server $server, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(EnableCronJob::class)->enable($server, $cronJob);

        Toast::success('Cronjob enabled successfully.');

        return back();
    }

    public function disable(Server $server, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(DisableCronJob::class)->disable($server, $cronJob);

        Toast::success('Cronjob disabled successfully.');

        return back();
    }
}
