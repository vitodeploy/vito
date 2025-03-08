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

    public function gitlab(): SourceControl
    {
        /** @var SourceControl $sourceControl */
        $sourceControl = $this->state(function (array $attributes) {
            return [
                'provider' => \App\Enums\SourceControl::GITLAB,
            ];
        });

        return $sourceControl;
    }

    public function github(): SourceControl
    {
        /** @var SourceControl $sourceControl */
        $sourceControl = $this->state(function (array $attributes) {
            return [
                'provider' => \App\Enums\SourceControl::GITLAB,
            ];
        });

        return $sourceControl;
    }

    public function bitbucket(): SourceControl
    {
        /** @var SourceControl $sourceControl */
        $sourceControl = $this->state(function (array $attributes) {
            return [
                'provider' => \App\Enums\SourceControl::BITBUCKET,
            ];
        });

        return $sourceControl;
    }
}
