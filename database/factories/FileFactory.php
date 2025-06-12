<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomNumber(), //
            'server_id' => $this->faker->randomNumber(),
            'server_user' => $this->faker->word(),
            'path' => $this->faker->word(),
            'type' => 'file',
            'name' => $this->faker->name(),
            'size' => $this->faker->randomNumber(),
            'links' => $this->faker->randomNumber(),
            'owner' => $this->faker->word(),
            'group' => $this->faker->word(),
            'date' => $this->faker->word(),
            'permissions' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
