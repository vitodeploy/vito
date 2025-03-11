<?php

namespace App\Actions\Monitoring;

use App\Models\Server;

class UpdateMetricSettings
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Server $server, array $input): void
    {
        $service = $server->monitoring();
        if (! $service instanceof \App\Models\Service) {
            throw new \Exception('Monitoring service not found');
        }
        /** @var \App\SSH\Services\ServiceInterface $handler */
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
