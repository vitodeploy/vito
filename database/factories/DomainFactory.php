<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dns_provider_id' => \App\Models\DNSProvider::factory(),
            'user_id' => \App\Models\User::factory(),
            'project_id' => \App\Models\Project::factory(),
            'domain' => $this->faker->domainName(),
            'provider_domain_id' => $this->faker->uuid(),
            'metadata' => [
                'name' => $this->faker->domainName(),
                'status' => 'active',
                'created_on' => $this->faker->dateTime()->format('Y-m-d\TH:i:s\Z'),
                'modified_on' => $this->faker->dateTime()->format('Y-m-d\TH:i:s\Z'),
            ],
        ];
    }
}
