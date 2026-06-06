<?php

namespace App\Actions\Worker;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Enums\WorkerStatus;
use App\Models\Server;
use App\Models\Service;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\HandlesWorkerFailure;

class SyncWorkerStatuses
{
    use HandlesWorkerFailure;

    private const LOG_TYPE = 'sync-worker-statuses-failed';

    public function sync(Server $server, ?Site $site = null): int
    {
        /** @var Service $service */
        $service = $server->processManager();
        /** @var ProcessManager $handler */
        $handler = $service->handler();

        $workers = $server->workers()
            ->when($site, fn ($query) => $query->where('site_id', $site->id))
            ->whereNotIn('status', [WorkerStatus::CREATING, WorkerStatus::DELETING])
            ->get();

        if ($workers->isEmpty()) {
            return 0;
        }

        $statuses = $handler->statuses();

        $changed = $workers->filter(fn (Worker $worker): bool => $this->settle($worker, $statuses[$worker->id] ?? []));

        $changed->loadMissing('site')
            ->pluck('site')
            ->filter()
            ->unique('id')
            ->each(fn (Site $workerSite) => app(BroadcastSiteUpdate::class)->broadcast($workerSite));

        return $changed->count();
    }

    /**
     * @param  array<string, array{state: string, description: string}>  $processes
     */
    private function settle(Worker $worker, array $processes): bool
    {
        [$status, $error] = $this->target($processes);

        if ($worker->status === $status && $worker->error === $error) {
            return false;
        }

        if ($status === WorkerStatus::FAILED) {
            $this->failWorker($worker, $error, self::LOG_TYPE, (string) $error);

            return true;
        }

        $worker->status = $status;
        $worker->error = null;
        $worker->save();

        $this->broadcastWorkerUpdate($worker);

        return true;
    }

    /**
     * @param  array<string, array{state: string, description: string}>  $processes
     * @return array{0: WorkerStatus, 1: ?string}
     */
    private function target(array $processes): array
    {
        if ($processes === []) {
            return [WorkerStatus::FAILED, 'Process not found in supervisor'];
        }

        $worst = WorkerStatus::RUNNING;
        $errors = [];

        foreach ($processes as $process => $info) {
            $status = $this->mapState($info['state']);

            if ($status === WorkerStatus::FAILED) {
                $errors[] = trim("{$process}: {$info['state']} {$info['description']}");
            }

            if ($this->severity($status) > $this->severity($worst)) {
                $worst = $status;
            }
        }

        if ($worst === WorkerStatus::FAILED) {
            return [WorkerStatus::FAILED, mb_substr(implode("\n", $errors), 0, 500)];
        }

        return [$worst, null];
    }

    private function mapState(string $state): WorkerStatus
    {
        return match ($state) {
            'RUNNING' => WorkerStatus::RUNNING,
            'STARTING' => WorkerStatus::STARTING,
            'STOPPING' => WorkerStatus::STOPPING,
            'STOPPED', 'EXITED' => WorkerStatus::STOPPED,
            default => WorkerStatus::FAILED,
        };
    }

    private function severity(WorkerStatus $status): int
    {
        return match ($status) {
            WorkerStatus::FAILED => 4,
            WorkerStatus::STOPPED => 3,
            WorkerStatus::STOPPING => 2,
            WorkerStatus::STARTING => 1,
            default => 0,
        };
    }
}
