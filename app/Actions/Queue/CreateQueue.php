<?php

namespace App\Actions\Queue;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateQueue
{
    /**
     * @throws ValidationException
     */
    public function create(mixed $queueable, array $input): void
    {
        $this->validate($input);

        $queue = new Queue([
            'server_id' => $queueable instanceof Server ? $queueable->id : $queueable->server_id,
            'site_id' => $queueable instanceof Site ? $queueable->id : null,
            'command' => $input['command'],
            'user' => $input['user'],
            'auto_start' => $input['auto_start'],
            'auto_restart' => $input['auto_restart'],
            'numprocs' => $input['numprocs'],
            'status' => QueueStatus::CREATING,
        ]);
        $queue->save();

        dispatch(function () use ($queue) {
            $queue->server->processManager()->handler()->create(
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

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        $rules = [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                'in:root,'.config('core.ssh_user'),
            ],
            'auto_start' => [
                'required',
                'in:0,1',
            ],
            'auto_restart' => [
                'required',
                'in:0,1',
            ],
            'numprocs' => [
                'required',
                'numeric',
                'min:1',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
