<?php

namespace Database\Factories;

use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Models\Component;
use App\Models\Environment;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Component>
 */
class ComponentFactory extends Factory
{
    protected $model = Component::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'server_id' => fn (array $attributes): int => Server::factory()->create([
                'project_id' => Environment::query()->findOrFail($attributes['environment_id'])->application->project_id,
            ])->id,
            'isolated_user_id' => null,
            'user' => fn (array $attributes): string => Server::query()->findOrFail($attributes['server_id'])->ssh_user,
            'name' => $this->faker->unique()->slug(2),
            'type' => ComponentType::APP,
            'path' => function (array $attributes): string {
                $environment = Environment::query()->findOrFail($attributes['environment_id']);

                return '/home/'.$attributes['user'].'/app-'.$environment->application_id.'-'.$environment->slug;
            },
            'status' => ComponentStatus::PENDING,
            'last_error' => null,
            'needs_redeploy' => false,
        ];
    }
}
