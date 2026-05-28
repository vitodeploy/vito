<?php

namespace App\Traits;

use App\DTOs\SocketEventDTO;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Exceptions\SSHError;
use App\Http\Resources\WorkerResource;
use App\Models\ServerLog;
use App\Models\Worker;
use Throwable;

trait HandlesWorkerFailure
{
    /**
     * @var list<string>
     */
    private static array $benignSupervisorPhrases = ['already started', 'not running'];

    protected function markWorkerFailed(Worker $worker, Throwable $e, string $logType): void
    {
        $this->failWorker($worker, $this->extractError($e), $logType, $e->getMessage());
    }

    protected function failWorker(Worker $worker, ?string $error, string $logType, string $logMessage, WorkerStatus $status = WorkerStatus::FAILED): void
    {
        $worker->error = $error;
        $worker->status = $status;
        $worker->save();

        $this->broadcastWorkerUpdate($worker);

        ServerLog::log($worker->server, $logType, $logMessage);
    }

    protected function isBenignSupervisorStatus(string $status): bool
    {
        foreach (self::$benignSupervisorPhrases as $phrase) {
            if (str_contains($status, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function extractError(Throwable $e): ?string
    {
        $message = $e instanceof SSHError
            ? ($this->supervisorErrorLines($e) ?? trim($e->getMessage()))
            : trim($e->getMessage());

        if ($message === '') {
            return null;
        }

        return mb_substr($message, 0, 500);
    }

    private function supervisorErrorLines(SSHError $e): ?string
    {
        $log = $e->getLog();
        if ($log === null || $log->is_remote) {
            return null;
        }

        $content = $log->getContent(50);
        if (! is_string($content) || $content === '') {
            return null;
        }

        $lines = collect(explode("\n", $content))
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => str_contains($line, ': ERROR')
                && ! $this->isBenignSupervisorStatus($line))
            ->unique()
            ->values();

        return $lines->isEmpty() ? null : $lines->implode("\n");
    }

    protected function broadcastWorkerUpdate(Worker $worker): void
    {
        $worker->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $worker->server->project_id,
            type: 'worker.updated',
            data: new WorkerResource($worker),
        ));
    }
}
