<?php

namespace App\Actions\Monitoring;

use App\Models\Server;
use App\Models\Service;
use App\SSH\Services\ServiceInterface;

class UpdateMetricSettings
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Server $server, array $input): void
    {
        /** @var Service $service */
        $service = $server->monitoring();
        /** @var ServiceInterface $handler */
        $handler = $service->handler();
        $data = $handler->data();
        $data['data_retention'] = $input['data_retention'];
        $service->type_data = $data;
        $service->save();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'data_retention' => [
                'required',
                'numeric',
                'min:1',
            ],
        ];
    }
}
