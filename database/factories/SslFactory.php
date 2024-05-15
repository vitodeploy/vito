<?php

namespace Database\Factories;

use App\Enums\SslStatus;
use App\Models\Ssl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SslFactory extends Factory
{
    protected $model = Ssl::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'certificate' => $this->faker->word(),
            'pk' => $this->faker->word(),
            'ca' => $this->faker->word(),
            'expires_at' => Carbon::now()->addDay(),
            'status' => SslStatus::CREATED,
            'domains' => ['example.com'],
        ];
    }
}
