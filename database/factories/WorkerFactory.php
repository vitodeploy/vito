<?php

namespace Database\Factories;

use App\Enums\WorkerStatus;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    protected $model = Worker::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'command' => 'php artisan queue:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'redirect_stderr' => 1,
            'stdout_logfile' => 'file.log',
            'status' => WorkerStatus::CREATING,
        ];
    }
}
