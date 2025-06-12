<?php

namespace Database\Factories;

use App\Enums\CommandExecutionStatus;
use App\Models\Command;
use App\Models\CommandExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommandExecution>
 */
class CommandExecutionFactory extends Factory
{
    protected $model = CommandExecution::class;

    public function definition(): array
    {
        return [
            'command_id' => Command::factory(),
            'status' => $this->faker->randomElement([
                CommandExecutionStatus::COMPLETED,
                CommandExecutionStatus::FAILED,
                CommandExecutionStatus::EXECUTING,
            ]),
            'variables' => [],
        ];
    }
}
