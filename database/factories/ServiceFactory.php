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
            'status' => ServiceStatus::READY,
        ];
    }
}
