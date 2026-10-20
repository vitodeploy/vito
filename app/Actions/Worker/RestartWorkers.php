<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\HandlesWorkerFailure;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class RestartWorkers
{
    use HandlesWorkerFailure;

    private const LOG_TYPE = 'deploy-restart-worker-failed';

    /**
     * @param  Collection<int, Worker>  $workers
     */
    public function restart(Server $server, Collection $workers, ?ServerLog $log = null, ?int $siteId = null): void
    {
        if ($workers->isEmpty()) {
            return;
        }

        try {
            $service = $server->processManager() ?? throw new RuntimeException('Process manager service not found.');
            /** @var ProcessManager $handler */
            $handler = $service->handler();
            $workers->each(fn (Worker $worker) => $handler->writeConfig($worker));
            $output = $handler->restartMany($workers->pluck('id')->all(), $siteId);
        } catch (Throwable $e) {
            $workers->each(fn (Worker $worker) => $this->markWorkerFailed($worker, $e, self::LOG_TYPE));
            $log?->write('Failed to restart workers. See worker statuses for details.');

            return;
        }

        $statuses = $this->parseStatuses($output);

        $failed = $workers->reject(fn (Worker $worker): bool => $this->settle($worker, $statuses[$worker->id] ?? []));

        if ($failed->isNotEmpty()) {
            $log?->write('Failed to restart worker(s): '.$failed->pluck('id')->implode(', '));
        }
    }

    /**
     * @param  array<string, string>  $processes  process token => last reported status
     */
    private function settle(Worker $worker, array $processes): bool
    {
        if ($processes === []) {
            $this->fail($worker, 'Unable to restart');

            return false;
        }

        $errors = $this->errorLines($processes);

        if ($errors !== []) {
            $this->fail($worker, mb_substr(implode("\n", $errors), 0, 500));

            return false;
        }

        if (! $this->allStarted($processes)) {
            $this->fail($worker, 'Unable to restart (stopped)', WorkerStatus::STOPPED);

            return false;
        }

        $worker->status = WorkerStatus::RUNNING;
        $worker->error = null;
        $worker->save();

        $this->broadcastWorkerUpdate($worker);

        return true;
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

            if ($group === false || ! ctype_digit($group) || ! str_contains($process, ':')) {
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
