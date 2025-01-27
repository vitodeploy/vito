<?php

namespace App\Actions\Queue;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\Server;
use App\Models\Site;
use App\SSH\Services\ProcessManager\ProcessManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateQueue
{
    /**
     * @throws ValidationException
     */
    public function create(mixed $queueable, array $input): void
    {
        $queue = new Queue([
            'server_id' => $queueable instanceof Server ? $queueable->id : $queueable->server_id,
            'site_id' => $queueable instanceof Site ? $queueable->id : null,
            'command' => $input['command'],
            'user' => $input['user'],
            'auto_start' => $input['auto_start'] ? 1 : 0,
            'auto_restart' => $input['auto_restart'] ? 1 : 0,
            'numprocs' => $input['numprocs'],
            'status' => QueueStatus::CREATING,
        ]);
        $queue->save();

        dispatch(function () use ($queue) {
            /** @var ProcessManager $processManager */
            $processManager = $queue->server->processManager()->handler();
            $processManager->create(
                $queue->id,
                $queue->command,
                $queue->user,
                $queue->auto_start,
                $queue->auto_restart,
                $queue->numprocs,
                $queue->getLogFile(),
                $queue->site_id
            );
            $queue->status = QueueStatus::RUNNING;
            $queue->save();
        })->catch(function () use ($queue) {
            $queue->delete();
        })->onConnection('ssh');
    }

    public static function rules(Site $site): array
    {
        return [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in([
                    'root',
                    $site->user,
                ]),
            ],
            'numprocs' => [
                'required',
                'numeric',
                'min:1',
            ],
        ];
    }
}
