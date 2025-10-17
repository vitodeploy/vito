<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DNSProvider>
 */
class DNSProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'project_id' => null,
            'provider' => 'cloudflare',
            'name' => $this->faker->word(),
            'credentials' => [
                'token' => $this->faker->sha256(),
            ],
            'connected' => true,
        ];
    }
}
