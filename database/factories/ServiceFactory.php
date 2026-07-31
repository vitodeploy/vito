<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'server_id' => 1,
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ];
    }

    public function vitoAgent(): static
    {
        return $this->state(fn (): array => [
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'type_data' => [
                'url' => 'https://vito.test/agent-endpoint',
                'secret' => 'agent-secret',
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }
}
