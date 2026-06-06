<?php

namespace App\Jobs\Worker;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Actions\Worker\SyncWorkerStatuses;
use App\Enums\WorkerStatus;
use App\Models\Server;
use App\Models\Service;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\HandlesWorkerFailure;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RestartAllJob implements ShouldQueue
{
    use HandlesWorkerFailure;
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Server $server,
        protected ?Site $site = null,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            /** @var Service $service */
            $service = $this->server->processManager();
            /** @var ProcessManager $handler */
            $handler = $service->handler();
            $handler->restartAll($this->site?->id);

            app(SyncWorkerStatuses::class)->sync($this->server, $this->site);
        });
    }

    public function failed(Throwable $e): void
    {
        $failed = $this->server->workers()
            ->when($this->site, fn ($query) => $query->where('site_id', $this->site->id))
            ->where('status', WorkerStatus::RESTARTING)
            ->get()
            ->each(fn (Worker $worker) => $this->markWorkerFailed($worker, $e, 'restart-all-workers-failed'));

        $failed->loadMissing('site')
            ->pluck('site')
            ->filter()
            ->unique('id')
            ->each(fn (Site $workerSite) => app(BroadcastSiteUpdate::class)->broadcast($workerSite));
    }
}
