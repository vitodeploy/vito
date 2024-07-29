<?php

namespace Database\Factories;

use App\Enums\ScriptExecutionStatus;
use App\Models\ScriptExecution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScriptExecutionFactory extends Factory
{
    protected $model = ScriptExecution::class;

    public function definition(): array
    {
        return [
            'user' => 'root',
            'variables' => [],
            'status' => ScriptExecutionStatus::EXECUTING,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
