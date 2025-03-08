<?php

namespace Database\Factories;

use App\Models\DatabaseUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DatabaseUser>
 */
class DatabaseUserFactory extends Factory
{
    protected $model = DatabaseUser::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->userName,
            'password' => 'password',
            'databases' => [],
            'host' => '%',
        ];
    }
}
