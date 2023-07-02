<?php

namespace Database\Factories;

use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScriptExecutionFactory extends Factory
{
    protected $model = ScriptExecution::class;

    public function definition(): array
    {
        return [
            'user' => $this->faker->word(),
            'finished_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'script_id' => Script::factory(),
            'server_id' => Server::factory(),
        ];
    }
}
