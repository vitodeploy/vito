<?php

namespace Database\Factories;

use App\Enums\RedirectStatus;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from' => $this->faker->word,
            'to' => $this->faker->url,
            'mode' => $this->faker->randomElement([301, 302, 307, 308]),
            'status' => RedirectStatus::READY,
        ];
    }
}
