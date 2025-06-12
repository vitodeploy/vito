<?php

namespace Database\Factories;

use App\Models\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => $this->faker->text(10),
            'provider' => 'email',
            'data' => [
                'email' => $this->faker->email,
            ],
            'connected' => 1,
        ];
    }
}
