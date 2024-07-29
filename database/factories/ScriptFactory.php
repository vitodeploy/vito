<?php

namespace Database\Factories;

use App\Models\Script;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScriptFactory extends Factory
{
    protected $model = Script::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'content' => 'ls -la',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
