<?php

namespace Database\Factories;

use App\Models\DeploymentScript;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DeploymentScriptFactory extends Factory
{
    protected $model = DeploymentScript::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'content' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'site_id' => Site::factory(),
        ];
    }
}
