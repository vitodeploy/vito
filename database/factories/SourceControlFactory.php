<?php

namespace Database\Factories;

use App\Models\SourceControl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SourceControlFactory extends Factory
{
    protected $model = SourceControl::class;

    public function definition(): array
    {
        return [
            'access_token' => Str::random(10),
        ];
    }

    public function gitlab(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => \App\Enums\SourceControl::GITLAB,
            ];
        });
    }

    public function github(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => \App\Enums\SourceControl::GITHUB,
            ];
        });
    }

    public function bitbucket(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => \App\Enums\SourceControl::BITBUCKET,
            ];
        });
    }
}
