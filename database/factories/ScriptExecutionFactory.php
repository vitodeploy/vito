<?php

namespace Database\Factories;

use App\Models\ScriptExecution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScriptExecutionFactory extends Factory
{
    protected $model = ScriptExecution::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(), //
            'updated_at' => Carbon::now(),
            'script_id' => $this->faker->randomNumber(),
            'variables' => $this->faker->words(),
            'status' => $this->faker->word(),
            'server_log_id' => $this->faker->randomNumber(),
        ];
    }
}
