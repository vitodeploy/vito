<?php

namespace App\Actions\Worker;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Enums\WorkerStatus;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\HandlesWorkerFailure;
use Throwable;

class RestartSiteWorkers
{
    use HandlesWorkerFailure;

    private const LOG_TYPE = 'deploy-restart-worker-failed';

    public function restart(Site $site): void
    {
        /** @var ProcessManager $handler */
        $handler = $site->server->processManager()->handler();

        $workers = $site->loadMissing('workers')->workers;

        if ($workers->isEmpty()) {
            return;
        }

        try {
            $output = $handler->restartMany($workers->pluck('id')->all(), $site->id);
        } catch (Throwable $e) {
            $workers->each(fn (Worker $worker) => $this->markWorkerFailed($worker, $e, self::LOG_TYPE));
            app(BroadcastSiteUpdate::class)->broadcast($site);

            return;
        }

        $statuses = $this->parseStatuses($output);

        $workers->each(fn (Worker $worker) => $this->settle($worker, $statuses[$worker->id] ?? []));

        app(BroadcastSiteUpdate::class)->broadcast($site);
    }

    /**
     * @param  array<string, string>  $processes  process token => last reported status
     */
    private function settle(Worker $worker, array $processes): void
    {
        if ($processes === []) {
            $this->fail($worker, 'Unable to restart');

            return;
        }

        $errors = $this->errorLines($processes);

        if ($errors !== []) {
            $this->fail($worker, mb_substr(implode("\n", $errors), 0, 500));

            return;
        }

        if (! $this->allStarted($processes)) {
            $this->fail($worker, 'Unable to restart (stopped)', WorkerStatus::STOPPED);

            return;
        }

        $worker->status = WorkerStatus::RUNNING;
        $worker->error = null;
        $worker->save();

        $this->broadcastWorkerUpdate($worker);
    }

    private function fail(Worker $worker, string $error, WorkerStatus $status = WorkerStatus::FAILED): void
    {
        $this->failWorker($worker, $error, self::LOG_TYPE, $error, $status);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseStatuses(string $output): array
    {
        $statuses = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if (! str_contains($line, ': ')) {
                continue;
            }

            [$process, $status] = explode(': ', $line, 2);
            $group = strtok($process, ':');

            if ($group === false || ! ctype_digit($group)) {
                continue;
            }

            $statuses[(int) $group][$process] = $status;
        }

        return $statuses;
    }

    /**
     * @param  array<string, string>  $processes
     * @return array<int, string>
     */
    private function errorLines(array $processes): array
    {
        $errors = [];

        foreach ($processes as $process => $status) {
            if (str_starts_with($status, 'ERROR') && ! $this->isBenignSupervisorStatus($status)) {
                $errors[] = $process.': '.$status;
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, string>  $processes
     */
    private function allStarted(array $processes): bool
    {
        $started = false;

        foreach ($processes as $status) {
            if ($this->isBenignSupervisorStatus($status)) {
                continue;
            }

            if ($status !== 'started') {
                return false;
            }

            $started = true;
        }

        return $started;
    }
}
