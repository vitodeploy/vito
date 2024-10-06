<?php

namespace App\Actions\Queue;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\Server;
use App\SSH\Services\ProcessManager\ProcessManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditQueue
{
    /**
     * @throws ValidationException
     */
    public function edit(Queue $queue, array $input): void
    {
        $queue->fill([
            'command' => $input['command'],
            'user' => $input['user'],
            'auto_start' => $input['auto_start'] ? 1 : 0,
            'auto_restart' => $input['auto_restart'] ? 1 : 0,
            'numprocs' => $input['numprocs'],
            'status' => QueueStatus::RESTARTING,
        ]);
        $queue->save();

        dispatch(function () use ($queue) {
            /** @var ProcessManager $processManager */
            $processManager = $queue->server->processManager()->handler();
            $processManager->delete($queue->id, $queue->site_id);

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
            $queue->status = QueueStatus::FAILED;
            $queue->save();
        })->onConnection('ssh');
    }

    public static function rules(Server $server): array
    {
        return [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in([
                    'root',
                    $server->ssh_user,
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
