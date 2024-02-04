<?php

namespace Database\Factories;

use App\Models\GitHook;
use App\Models\Site;
use App\Models\SourceControl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GitHookFactory extends Factory
{
    protected $model = GitHook::class;

    public function definition(): array
    {
        return [
            'secret' => $this->faker->word(),
            'events' => $this->faker->words(),
            'actions' => $this->faker->words(),
            'hook_id' => $this->faker->word(),
            'hook_response' => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'site_id' => Site::factory(),
            'source_control_id' => SourceControl::factory(),
        ];
    }
}
