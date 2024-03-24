<?php

namespace Tests\Unit\Commands;

use App\Facades\SSH;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_without_any_backups(): void
    {
        $this->artisan('backups:run "* * * * *"')
            ->expectsOutput('0 backups started');
    }

    public function test_run_backups(): void
    {
        SSH::fake();

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX,
        ]);

        $backup = Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
            'interval' => '1 * * * *',
            'keep_backups' => 10,
        ]);

        $this->artisan('backups:run "1 * * * *"')
            ->expectsOutput('1 backups started');
    }
}
