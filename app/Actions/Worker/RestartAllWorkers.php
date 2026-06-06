<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Jobs\Worker\RestartAllJob;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;

class RestartAllWorkers
{
    public function restart(Server $server, ?Site $site = null): void
    {
        $server->workers()
            ->when($site, fn ($query) => $query->where('site_id', $site->id))
            ->whereNotIn('status', [WorkerStatus::CREATING, WorkerStatus::DELETING])
            ->get()
            ->each(function (Worker $worker): void {
                $worker->status = WorkerStatus::RESTARTING;
                $worker->error = null;
                $worker->save();
            });

        dispatch(new RestartAllJob($server, $site))->onQueue('ssh');
    }
}
