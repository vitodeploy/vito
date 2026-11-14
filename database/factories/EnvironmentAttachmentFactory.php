<?php

namespace Database\Factories;

use App\Enums\EnvironmentAttachmentStatus;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EnvironmentAttachment> */
class EnvironmentAttachmentFactory extends Factory
{
    protected $model = EnvironmentAttachment::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'attachable_type' => (new Database)->getMorphClass(),
            'attachable_id' => Database::factory(),
            'server_id' => fn (array $attributes): int => Database::query()->findOrFail($attributes['attachable_id'])->server_id,
            'credentials' => ['username' => 'vito_'.strtolower(Str::random(8)), 'password' => Str::random(32)],
            'access' => [],
            'status' => EnvironmentAttachmentStatus::PENDING,
        ];
    }
}
