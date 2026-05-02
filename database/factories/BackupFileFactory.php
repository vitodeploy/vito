<?php

namespace Database\Factories;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupFile>
 */
class BackupFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->slug().'-'.now()->format('YmdHis'),
            'size' => $this->faker->numberBetween(1000, 10000000),
            'status' => BackupFileStatus::CREATED,
        ];
    }
}
