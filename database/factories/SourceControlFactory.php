<?php

namespace Database\Factories;

use App\Models\SourceControl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\SourceControl>
 */
class SourceControlFactory extends Factory
{
    protected $model = SourceControl::class;

    public function definition(): array
    {
        return [
            'access_token' => Str::random(10),
            'provider' => \App\Enums\SourceControl::GITHUB,
            'profile' => $this->faker->name,
            'project_id' => null,
        ];
    }

    /**
     * @return Factory<\App\Models\SourceControl>
     */
    public function gitlab(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => \App\Enums\SourceControl::GITLAB,
        ]);
    }

    /**
     * @return Factory<\App\Models\SourceControl>
     */
    public function github(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => \App\Enums\SourceControl::GITHUB,
        ]);
    }

    /**
     * @return Factory<\App\Models\SourceControl>
     */
    public function bitbucket(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => \App\Enums\SourceControl::BITBUCKET,
        ]);
    }
}
