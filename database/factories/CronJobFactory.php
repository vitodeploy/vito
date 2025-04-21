<?php

namespace Database\Factories;

use App\Enums\CronjobStatus;
use App\Models\CronJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CronJob>
 */
class CronJobFactory extends Factory
{
    protected $model = CronJob::class;

    public function definition(): array
    {
        return [
            'server_id' => 1,
            'command' => 'ls -la',
            'user' => 'root',
            'frequency' => '* * * * *',
            'hidden' => false,
            'status' => CronjobStatus::READY,
        ];
    }
}
