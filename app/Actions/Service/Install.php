<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHError;
use App\Jobs\Service\InstallJob;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Install
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function install(Server $server, array $input): Service
    {
        $this->validate($input);

        $name = $input['name'];
        $input['type'] = config("service.services.$name.type");

        if (! $input['type']) {
            throw new \InvalidArgumentException("Service type is not defined for $name");
        }

        $service = new Service([
            'server_id' => $server->id,
            'name' => $input['name'],
            'type' => $input['type'],
            'version' => $input['version'],
            'status' => ServiceStatus::INSTALLING,
        ]);
        $service->is_default = ! $server->defaultService($input['type']);

        Validator::make($input, $service->handler()->creationRules($input))->validate();

        $service->type_data = $service->handler()->creationData($input);

        $service->save();

        dispatch(new InstallJob($service))->onQueue('ssh');

        return $service;
    }

    private function validate(array $input): void
    {
        $rules = [
            'name' => [
                'required',
                Rule::in(array_keys(config('service.services'))),
            ],
            'version' => [
                'required',
            ],
        ];
        if (isset($input['name'])) {
            $rules['version'][] = Rule::in(config("service.services.{$input['name']}.versions", []));
        }

        Validator::make($input, $rules)->validate();
    }
}
