<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Install
{
    public function install(Server $server, array $input): Service
    {
        $this->validate($server, $input);

        $service = new Service([
            'server_id' => $server->id,
            'name' => $input['name'],
            'type' => $input['type'],
            'version' => $input['version'],
            'status' => ServiceStatus::INSTALLING,
        ]);

        Validator::make($input, $service->handler()->creationRules($input))->validate();

        $service->type_data = $service->handler()->creationData($input);

        $service->save();

        dispatch(function () use ($service) {
            $service->handler()->install();
            $service->status = ServiceStatus::READY;
            $service->save();
        })->catch(function () use ($service) {
            $service->status = ServiceStatus::INSTALLATION_FAILED;
            $service->save();
        })->onConnection('ssh');

        return $service;
    }

    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'type' => [
                'required',
                Rule::in(config('core.service_types')),
            ],
            'name' => [
                'required',
                Rule::in(array_keys(config('core.service_types'))),
            ],
            'version' => 'required',
        ])->validate();
    }
}
