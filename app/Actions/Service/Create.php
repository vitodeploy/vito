<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Create
{
    public function create(Server $server, array $input): Service
    {
        $this->validate($server, $input);

        $service = new Service([
            'name' => $input['type'],
            'type' => $input['type'],
            'version' => $input['version'],
            'status' => ServiceStatus::INSTALLING,
        ]);

        Validator::make($input, $service->handler()->creationRules($input))->validate();

        $service->type_data = $service->handler()->creationData($input);

        $service->save();

        $service->handler()->create();

        dispatch(function () use ($service) {
            $service->handler()->install();
            $service->status = ServiceStatus::READY;
            $service->save();
        })->catch(function () use ($service) {
            $service->handler()->delete();
            $service->delete();
        })->onConnection('ssh');

        return $service;
    }

    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'type' => [
                'required',
                Rule::in(config('core.add_on_services')),
                Rule::unique('services', 'type')->where('server_id', $server->id),
            ],
            'version' => 'required',
        ])->validate();
    }
}
