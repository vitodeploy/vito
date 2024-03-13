<?php

namespace App\Http\Controllers;

use App\Actions\Queue\CreateQueue;
use App\Actions\Queue\DeleteQueue;
use App\Actions\Queue\ManageQueue;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Queue;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index(Server $server, Site $site): View
    {
        return view('queues.index', [
            'server' => $server,
            'site' => $site,
            'queues' => $site->queues,
        ]);
    }

    public function store(Server $server, Site $site, Request $request): HtmxResponse
    {
        app(CreateQueue::class)->create($site, $request->input());

        Toast::success('Queue is being created.');

        return htmx()->back();
    }

    public function action(Server $server, Site $site, Queue $queue, string $action): HtmxResponse
    {
        app(ManageQueue::class)->{$action}($queue);

        Toast::success('Queue is about to '.$action);

        return htmx()->back();
    }

    public function destroy(Server $server, Site $site, Queue $queue): RedirectResponse
    {
        app(DeleteQueue::class)->delete($queue);

        Toast::success('Queue is being deleted.');

        return back();
    }
}
