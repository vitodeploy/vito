<?php

namespace Database\Factories;

use App\Models\ServerTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerTemplate>
 */
class ServerTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->word(),
            'services' => [
                'php' => '8.4',
            ],
        ];
    }
}
