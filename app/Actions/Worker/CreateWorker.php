<?php

namespace App\Actions\Worker;

use App\Actions\Component\ValidateComponentProcess;
use App\Enums\WorkerStatus;
use App\Jobs\Worker\CreateJob;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateWorker
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input, ?Site $site = null, ?Component $component = null, ?ComponentCapability $managedBy = null): Worker
    {
        $worker = app(ValidateComponentProcess::class)->run($managedBy ? null : $component, function () use ($server, $input, $site, $component, $managedBy): Worker {
            app(ValidateComponentProcess::class)->validate($server, $input, $component, 'worker', managedBy: $managedBy);
            if ($component) {
                $input['user'] = $component->user;
            }
            $this->validate($server, $input, $site, $component);

            $siteId = $site?->id;
            if (! $site && isset($input['site_id']) && ! empty($input['site_id'])) {
                $siteId = (int) $input['site_id'];
            }

            $worker = new Worker([
                'server_id' => $server->id,
                'site_id' => $siteId,
                'name' => $input['name'],
                'command' => $input['command'],
                'user' => $input['user'],
                'auto_start' => $input['auto_start'] ? 1 : 0,
                'auto_restart' => $input['auto_restart'] ? 1 : 0,
                'numprocs' => $input['numprocs'],
                'environment' => isset($input['environment'])
                    ? UpdateWorkerEnvironment::processVariables($input['environment'], null)
                    : null,
                'status' => WorkerStatus::CREATING,
            ]);
            $worker->component_id = $component?->id;
            $worker->component_capability_id = $managedBy?->id;
            $worker->save();

            return $worker;
        });

        if ($managedBy) {
            app(InstallWorker::class)->install($worker);
        } else {
            dispatch(new CreateJob($worker))->onQueue('ssh')->afterCommit();
        }

        return $worker;
    }

    private function validate(Server $server, array $input, ?Site $site = null, ?Component $component = null): void
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workers')->where(function ($query) use ($server, $site, $component) {
                    return $query->where('server_id', $server->id)->where('component_id', $component?->id)
                        ->where(function ($query) use ($site) {
                            if ($site) {
                                $query->where('site_id', $site->id);
                            }
                        });
                }),
            ],
            'command' => [
                'required', 'string', 'max:65535', 'not_regex:/\x00/',
            ],
            'user' => [
                'required',
                Rule::in($component ? [$component->user] : ($site?->getSshUsers() ?? $server->getSshUsers())),
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
            'environment' => [
                'sometimes',
                'nullable',
                'array',
                'max:100',
            ],
            ...UpdateWorkerEnvironment::nestedRules('environment'),
        ];

        if (isset($input['site_id']) && ! empty($input['site_id'])) {
            $rules['site_id'] = [
                'required',
                'integer',
                Rule::exists('sites', 'id')->where('server_id', $server->id),
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
