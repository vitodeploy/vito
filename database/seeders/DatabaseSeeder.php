<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

class DatabaseSeeder extends Seeder
{
    use WithFaker;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProjectsSeeder::class,
            UsersSeeder::class,
            TagsSeeder::class,
            ServerProvidersSeeder::class,
            StorageProvidersSeeder::class,
            SourceControlsSeeder::class,
            NotificationChannelsSeeder::class,
            ServersSeeder::class,
            SitesSeeder::class,
            DatabasesSeeder::class,
            CronJobsSeeder::class,
            SshKeysSeeder::class,
            MetricsSeeder::class,
            ServerLogsSeeder::class,
        ]);
    }
}
