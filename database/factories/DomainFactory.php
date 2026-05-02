<?php

namespace Database\Factories;

use App\Models\DNSProvider;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
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
            'dns_provider_id' => DNSProvider::factory(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
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
