<?php

namespace Database\Factories;

use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'database',
            'interval' => '0 * * * *',
            'keep_backups' => 10,
            'status' => null,
            'enabled' => true,
        ];
    }
}
