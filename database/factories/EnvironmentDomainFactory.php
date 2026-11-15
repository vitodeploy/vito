<?php

namespace Database\Factories;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Models\Component;
use App\Models\EnvironmentDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnvironmentDomain> */
class EnvironmentDomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'environment_id' => fn (array $attributes): int => Component::query()->findOrFail($attributes['component_id'])->environment_id,
            'host' => $this->faker->unique()->domainName(),
            'type' => HostedDomainType::ALIAS,
            'ssl_method' => SslMethod::NONE,
            'force_ssl' => false,
            'status' => HostedDomainStatus::PENDING,
        ];
    }
}
