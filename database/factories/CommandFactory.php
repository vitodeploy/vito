<?php

namespace Database\Factories;

use App\Models\Command;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Command>
 */
class CommandFactory extends Factory
{
    protected $model = Command::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'command' => 'php artisan '.$this->faker->word,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'site_id' => Site::factory(),
        ];
    }
}
