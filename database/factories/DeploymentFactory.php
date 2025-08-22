<?php

namespace Database\Factories;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deployment>
 */
class DeploymentFactory extends Factory
{
    protected $model = Deployment::class;

    public function definition(): array
    {
        return [
            'site_id' => 1,
            'deployment_script_id' => 1,
            'log_id' => 1,
            'commit_id' => 'id',
            'commit_data' => [],
            'status' => DeploymentStatus::FINISHED,
        ];
    }
}
