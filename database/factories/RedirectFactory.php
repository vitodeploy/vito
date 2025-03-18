<?php

namespace Database\Factories;

use App\Models\Redirect;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class RedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from' => $this->faker->word,
            'to' => $this->faker->url,
            'mode' => $this->faker->randomElement([301, 302, 307, 308]),
        ];
    }
}