<?php

namespace Database\Factories;

use App\Models\SshKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SshKey>
 */
class SshKeyFactory extends Factory
{
    protected $model = SshKey::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->name(),
            'public_key' => 'public-key-content',
        ];
    }
}
