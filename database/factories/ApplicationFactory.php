<?php

namespace Database\Factories;

use App\ApplicationTypes\ReverseProxy;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'server_id' => 1,
            'type' => ReverseProxy::id(),
            'type_data' => [
                'host' => 'localhost',
                'port' => 3000,
                'scheme' => 'http',
                'websocket' => false,
            ],
            'domain' => 'proxy.test',
            'aliases' => [],
            'status' => ApplicationStatus::READY,
            'force_ssl' => false,
        ];
    }
}
