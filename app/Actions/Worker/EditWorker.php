<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Models\Server;
use App\Models\Service;
use App\Models\Site;
use App\Models\Worker;
use App\SSH\Services\ProcessManager\ProcessManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditWorker
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(Worker $worker, array $input): void
    {
        $worker->fill([
            'command' => $input['command'],
            'user' => $input['user'],
            'auto_start' => $input['auto_start'] ? 1 : 0,
            'auto_restart' => $input['auto_restart'] ? 1 : 0,
            'numprocs' => $input['numprocs'],
            'status' => WorkerStatus::RESTARTING,
        ]);
        $worker->save();

        dispatch(function () use ($worker): void {
            /** @var Service $service */
            $service = $worker->server->processManager();
            /** @var ProcessManager $processManager */
            $processManager = $service->handler();
            $processManager->delete($worker->id, $worker->site_id);

            $processManager->create(
                $worker->id,
                $worker->command,
                $worker->user,
                $worker->auto_start,
                $worker->auto_restart,
                $worker->numprocs,
                $worker->getLogFile(),
                $worker->site_id
            );
            $worker->status = WorkerStatus::RUNNING;
            $worker->save();
        })->catch(function () use ($worker): void {
            $worker->status = WorkerStatus::FAILED;
            $worker->save();
        })->onConnection('ssh');
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Server $server, ?Site $site = null): array
    {
        return [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in($site?->getSshUsers() ?? $server->getSshUsers()),
            ],
            'numprocs' => [
                'required',
                'numeric',
                'min:1',
            ],
        ];
    }
}
