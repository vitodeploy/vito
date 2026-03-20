<?php

namespace Database\Factories;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Models\HostedDomain;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostedDomain>
 */
class HostedDomainFactory extends Factory
{
    protected $model = HostedDomain::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'domain' => $this->faker->domainName(),
            'type' => HostedDomainType::ALIAS,
            'status' => HostedDomainStatus::ACTIVE,
            'ssl_method' => SslMethod::LETSENCRYPT,
            'ssl_id' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => [
            'type' => HostedDomainType::PRIMARY,
        ]);
    }

    public function redirect(): static
    {
        return $this->state(fn () => [
            'type' => HostedDomainType::REDIRECT,
        ]);
    }
}
