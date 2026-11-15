<?php

namespace Database\Factories;

use App\Enums\EnvironmentDeploymentStatus;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EnvironmentDeployment> */
class EnvironmentDeploymentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'status' => EnvironmentDeploymentStatus::PENDING,
            'release' => (string) Str::ulid(),
            'branch' => 'main',
            'variables' => [],
        ];
    }
}
