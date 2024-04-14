<?php

namespace App\Actions\Monitoring;

use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateMetricSettings
{
    public function update(Server $server, array $input): void
    {
        $this->validate($input);

        $service = $server->monitoring();

        $data = $service->handler()->data();
        $data['data_retention'] = $input['data_retention'];
        $service->type_data = $data;
        $service->save();
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'data_retention' => [
                'required',
                Rule::in(config('core.metrics_data_retention')),
            ],
        ])->validate();
    }
}
