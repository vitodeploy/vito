<?php

namespace Database\Factories;

use App\Enums\SslStatus;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Ssl>
 */
class SslFactory extends Factory
{
    protected $model = Ssl::class;

    public function definition(): array
    {
        return [
            'site_id' => 1,
            'type' => $this->faker->word(),
            'certificate' => $this->faker->word(),
            'pk' => $this->faker->word(),
            'ca' => $this->faker->word(),
            'certificate_path' => '/etc/ssl/certs/'.$this->faker->word().'.pem',
            'pk_path' => '/etc/ssl/private/'.$this->faker->word().'.pem',
            'expires_at' => Carbon::now()->addDay(),
            'status' => SslStatus::CREATED,
            'domains' => ['example.com'],
        ];
    }

    public function serverLevel(): static
    {
        return $this->state(fn () => [
            'site_id' => null,
            'server_id' => Server::factory(),
            'type' => 'csr',
            'certificate' => null,
            'pk' => null,
            'ca' => null,
            'expires_at' => null,
            'domains' => null,
            'csr_data' => [
                'common_name' => 'example.com',
                'organization' => 'Test Org',
                'organizational_unit' => null,
                'city' => 'San Francisco',
                'state' => 'California',
                'country' => 'US',
                'email' => null,
                'key_size' => 2048,
            ],
        ]);
    }
}
