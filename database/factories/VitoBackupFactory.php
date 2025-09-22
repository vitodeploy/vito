<?php

namespace Database\Factories;

use App\Enums\VitoBackupStatus;
use App\Models\StorageProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VitoBackup>
 */
class VitoBackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' Backup',
            'frequency' => $this->faker->randomElement(['0 0 * * *', '0 0 * * 0', '0 0 1 * *']),
            'keep_backups' => $this->faker->numberBetween(3, 10),
            'storage_id' => StorageProvider::factory(),
            'status' => VitoBackupStatus::PENDING,
        ];
    }
}
