<?php

namespace Database\Factories;

use App\Enums\DatabaseStatus;
use App\Models\Database;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseFactory extends Factory
{
    protected $model = Database::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->userName,
            'status' => DatabaseStatus::READY,
        ];
    }
}
