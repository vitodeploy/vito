<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DNSRecord>
 */
class DNSRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['A', 'AAAA', 'CNAME', 'TXT'];
        $type = $this->faker->randomElement($types);

        return [
            'domain_id' => \App\Models\Domain::factory(),
            'type' => $type,
            'name' => $this->faker->domainName(),
            'content' => $this->getContentForType($type),
            'ttl' => $this->faker->randomElement([1, 300, 600, 3600]),
            'proxied' => $this->faker->boolean(),
            'provider_record_id' => $this->faker->uuid(),
            'metadata' => [
                'id' => $this->faker->uuid(),
                'created_on' => $this->faker->dateTime()->format('Y-m-d\TH:i:s\Z'),
                'modified_on' => $this->faker->dateTime()->format('Y-m-d\TH:i:s\Z'),
            ],
        ];
    }

    private function getContentForType(string $type): string
    {
        return match ($type) {
            'A' => $this->faker->ipv4(),
            'AAAA' => $this->faker->ipv6(),
            'CNAME' => $this->faker->domainName(),
            'TXT' => $this->faker->sentence(),
            default => $this->faker->word(),
        };
    }
}
