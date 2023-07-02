<?php

namespace Database\Factories;

use App\Enums\QueueStatus;
use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueFactory extends Factory
{
    protected $model = Queue::class;

    public function definition(): array
    {
        return [
            'command' => 'php artisan queue:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'redirect_stderr' => 1,
            'stdout_logfile' => 'file.log',
            'status' => QueueStatus::CREATING,
        ];
    }
}
