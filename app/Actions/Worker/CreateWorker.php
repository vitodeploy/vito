<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Models\Worker;
use App\Models\Server;
use App\Models\Service;
use App\Models\Site;
use App\SSH\Services\ProcessManager\ProcessManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateWorker
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input, ?Site $site = null): void
    {
        $worker = new Worker([
            'server_id' => $server->id,
            'site_id' => $site?->id,
            'command' => $input['command'],
            'user' => $input['user'],
            'auto_start' => $input['auto_start'] ? 1 : 0,
            'auto_restart' => $input['auto_restart'] ? 1 : 0,
            'numprocs' => $input['numprocs'],
            'status' => WorkerStatus::CREATING,
        ]);
        $worker->save();

        dispatch(function () use ($worker): void {
            /** @var Service $service */
            $service = $worker->server->processManager();
            /** @var ProcessManager $processManager */
            $processManager = $service->handler();
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
            $worker->delete();
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
