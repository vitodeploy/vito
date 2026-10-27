<?php

namespace Database\Factories;

use App\Enums\ComponentStatus;
use App\Models\Component;
use App\Models\ComponentCapability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentCapability>
 */
class ComponentCapabilityFactory extends Factory
{
    protected $model = ComponentCapability::class;

    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'capability' => 'web',
            'config' => null,
            'status' => ComponentStatus::PENDING,
        ];
    }
}
