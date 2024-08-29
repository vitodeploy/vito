<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'created_at' => Carbon::now(), //
            'updated_at' => Carbon::now(),
            'name' => $this->faker->randomElement(['production', 'staging', 'development']),
            'color' => $this->faker->randomElement(config('core.tag_colors')),
        ];
    }
}
