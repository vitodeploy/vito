<?php

namespace App\Actions\Worker;

use App\Actions\Component\ValidateComponentProcess;
use App\Models\Component;
use App\Enums\WorkerStatus;
use App\Jobs\Worker\EditJob;
use App\Models\Site;
use App\Models\Worker;
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
    public function edit(Worker $worker, array $input, ?Component $component = null): Worker
    {
        $component ??= $worker->component;
        $worker = app(ValidateComponentProcess::class)->run($component, function () use ($worker, $input, $component): Worker {
            $worker->refresh();
            app(ValidateComponentProcess::class)->validate($worker->server, $input, $component, 'worker', $worker);
            if ($component) {
                $input['user'] = $component->user;
            }
            if ($worker->isSiteBootstrap()) {
                throw ValidationException::withMessages([
                    'name' => "This worker is managed by its site. Edit the Start command on the site's Application page instead.",
                ]);
            }

            $this->validate($worker, $input, $worker->site);

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
            $worker->error = null;
            $worker->save();

            return $worker;
        });

        dispatch(new EditJob($worker))->onQueue('ssh')->afterCommit();

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
                    return $query->where('server_id', $worker->server_id)->where('component_id', $worker->component_id)
                        ->where(function ($query) use ($site) {
                            if ($site) {
                                $query->where('site_id', $site->id);
                            }
                        });
                })
                    ->ignore($worker->id),
            ],
            'command' => [
                'required', 'string', 'max:65535', 'not_regex:/\x00/',
            ],
            'user' => [
                'required',
                Rule::in($worker->component ? [$worker->component->user] : ($site?->getSshUsers() ?? $worker->server->getSshUsers())),
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
