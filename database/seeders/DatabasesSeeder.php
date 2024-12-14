<?php

namespace Database\Seeders;

use App\Enums\BackupFileStatus;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\StorageProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class DatabasesSeeder extends Seeder
{
    public function run(): void
    {
        $servers = Server::query()->whereHas('services', function (Builder $query) {
            $query->where('type', 'database');
        })->get();

        $storageProviders = StorageProvider::all();

        /** @var Server $server */
        foreach ($servers as $server) {
            /** @var Database $database */
            $database = Database::factory()->create([
                'server_id' => $server->id,
                'name' => 'main',
            ]);
            DatabaseUser::factory()->create([
                'server_id' => $server->id,
                'username' => 'main_user',
                'password' => 'password',
                'host' => '%',
                'databases' => [$database->name],
            ]);
            /** @var Backup $backup */
            $backup = Backup::factory()->create([
                'server_id' => $server->id,
                'database_id' => $database->id,
                'storage_id' => $storageProviders->random()->id,
            ]);
            BackupFile::factory(10)->create([
                'name' => $database->name.'-'.now()->format('Y-m-d-H-i-s').'.zip',
                'size' => rand(1000000, 10000000),
                'backup_id' => $backup->id,
                'status' => BackupFileStatus::CREATED,
            ]);
        }
    }
}
