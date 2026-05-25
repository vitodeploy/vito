<?php

namespace App\Traits;

use App\Actions\Site\BroadcastSiteUpdate;
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
    protected function markWorkerFailed(Worker $worker, Throwable $e, string $logType): void
    {
        $worker->error = $this->extractError($e);
        $worker->status = WorkerStatus::FAILED;
        $worker->save();

        $this->broadcastWorkerUpdate($worker);

        if ($worker->site) {
            app(BroadcastSiteUpdate::class)->broadcast($worker->site);
        }

        ServerLog::log($worker->server, $logType, $e->getMessage());
    }

    private function extractError(Throwable $e): ?string
    {
        if (! $e instanceof SSHError) {
            return null;
        }

        $message = $this->supervisorErrorLines($e) ?? trim($e->getMessage());

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
                && ! str_contains($line, 'already started')
                && ! str_contains($line, 'not running'))
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
