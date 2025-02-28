<?php

namespace Database\Factories;

use App\Models\Command;
use App\Models\CommandExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommandExecutionFactory extends Factory
{
    protected $model = CommandExecution::class;

    public function definition(): array
    {
        return [
            'command_id' => Command::factory(),
            'status' => $this->faker->randomElement(['running', 'completed', 'failed']),
            'user' => 'vito',
        ];
    }
}
