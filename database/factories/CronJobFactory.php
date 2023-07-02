<?php

namespace Database\Factories;

use App\Enums\CronjobStatus;
use App\Models\CronJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class CronJobFactory extends Factory
{
    protected $model = CronJob::class;

    public function definition(): array
    {
        return [
            'command' => 'ls -la',
            'user' => 'root',
            'frequency' => '* * * * *',
            'hidden' => false,
            'status' => CronjobStatus::READY,
        ];
    }
}
