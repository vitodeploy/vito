<?php

namespace Database\Factories;

use App\Models\ServerLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerLogFactory extends Factory
{
    protected $model = ServerLog::class;

    public function definition(): array
    {
        return [
            'type' => 'test-log',
            'name' => 'test.log',
            'disk' => 'server-logs',
        ];
    }
}
