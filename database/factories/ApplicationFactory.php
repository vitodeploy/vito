<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'source_control_id' => null,
            'name' => $this->faker->unique()->slug(2),
            'repository' => 'organization/repository',
            'runtime' => 'node',
        ];
    }
}
