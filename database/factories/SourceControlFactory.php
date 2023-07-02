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
            'provider' => $this->faker->randomElement(\App\Enums\SourceControl::getValues()),
            'access_token' => Str::random(10),
        ];
    }
}
