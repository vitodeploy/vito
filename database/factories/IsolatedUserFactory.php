<?php

namespace Database\Factories;

use App\Models\IsolatedUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IsolatedUser>
 */
class IsolatedUserFactory extends Factory
{
    protected $model = IsolatedUser::class;

    public function definition(): array
    {
        return [
            'server_id' => 1,
            'username' => $this->faker->unique()->userName(),
            'ssh_key' => null,
            'installed_tooling' => null,
        ];
    }
}
