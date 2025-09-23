<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Models\Service;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditWorker
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function edit(Worker $worker, array $input): Worker
    {
        $this->validate($worker, $input, $worker->site);

        // Determine site_id: use from input if provided
        $siteId = $worker->site_id;
        if (isset($input['site_id'])) {
            $siteId = ! empty($input['site_id']) ? (int) $input['site_id'] : null;
        }

        $worker->fill([
            'site_id' => $siteId,
            'name' => $input['name'],
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
                $worker->site?->path,
                $worker->site_id
            );
            $worker->status = WorkerStatus::RUNNING;
            $worker->save();
        })->catch(function () use ($worker): void {
            $worker->status = WorkerStatus::FAILED;
            $worker->save();
        })->onQueue('ssh');

        return $worker;
    }

    private function validate(Worker $worker, array $input, ?Site $site = null): void
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workers')->where(function ($query) use ($worker, $site) {
                    return $query->where('server_id', $worker->server_id)
                        ->where(function ($query) use ($site) {
                            if ($site) {
                                $query->where('site_id', $site->id);
                            }
                        });
                })
                    ->ignore($worker->id),
            ],
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in($site?->getSshUsers() ?? $worker->server->getSshUsers()),
            ],
            'auto_start' => [
                'required',
                'boolean',
            ],
            'auto_restart' => [
                'required',
                'boolean',
            ],
            'numprocs' => [
                'required',
                'numeric',
                'min:1',
            ],
        ];

        // Add site_id validation if provided in input
        if (isset($input['site_id']) && ! empty($input['site_id'])) {
            $rules['site_id'] = [
                'required',
                'integer',
                Rule::exists('sites', 'id')->where('server_id', $worker->server_id),
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
