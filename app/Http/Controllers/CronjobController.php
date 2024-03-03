<?php

namespace App\Http\Controllers;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
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
        return view('cronjobs.index', [
            'server' => $server,
            'cronjobs' => $server->cronJobs,
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        app(CreateCronJob::class)->create($server, $request->input());

        Toast::success('Cronjob created successfully.');

        return htmx()->back();
    }

    public function destroy(Server $server, CronJob $cronJob): RedirectResponse
    {
        app(DeleteCronJob::class)->delete($server, $cronJob);

        Toast::success('Cronjob deleted successfully.');

        return back();
    }
}
