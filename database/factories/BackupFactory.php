<?php

namespace Database\Factories;

use App\Enums\BackupStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'database',
            'interval' => '0 * * * *',
            'keep_backups' => 10,
            'status' => BackupStatus::RUNNING,
        ];
    }
}
