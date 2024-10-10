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
        $input['type'] = config('core.service_types')[$input['name']];

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

    public static function rules(array $input): array
    {
        $rules = [
            'name' => [
                'required',
                Rule::in(array_keys(config('core.service_types'))),
            ],
            'version' => [
                'required',
            ],
        ];
        if (isset($input['name'])) {
            $rules['version'][] = Rule::in(config("core.service_versions.{$input['name']}"));
        }

        return $rules;
    }
}
