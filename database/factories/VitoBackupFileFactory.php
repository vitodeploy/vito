<?php

namespace Database\Factories;

use App\Enums\VitoBackupFileStatus;
use App\Models\VitoBackup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VitoBackupFile>
 */
class VitoBackupFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vito_backup_id' => VitoBackup::factory(),
            'name' => 'vito-backup-'.$this->faker->date('Y-m-d').'.zip',
            'size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'status' => VitoBackupFileStatus::CREATED,
            'path' => 'backups/'.$this->faker->uuid().'.zip',
        ];
    }
}
