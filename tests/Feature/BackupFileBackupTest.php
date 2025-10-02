<?php

namespace Tests\Feature;

use App\Enums\BackupType;
use App\Models\Backup;
use App\Models\Server;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupFileBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_model_can_have_path_field(): void
    {
        $server = Server::factory()->create();
        $storage = StorageProvider::factory()->create();

        $backup = Backup::create([
            'type' => BackupType::FILE,
            'server_id' => $server->id,
            'storage_id' => $storage->id,
            'path' => '/var/www/html',
            'interval' => '0 0 * * *',
            'keep_backups' => 5,
            'status' => 'running',
        ]);

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals(BackupType::FILE, $backup->type);
        $this->assertEquals('/var/www/html', $backup->path);
        $this->assertNull($backup->database_id);
        $this->assertEquals($storage->id, $backup->storage_id);
    }

    public function test_backup_model_can_have_database_field(): void
    {
        $server = Server::factory()->create();
        $storage = StorageProvider::factory()->create();
        $database = $server->databases()->create([
            'name' => 'test_db',
            'status' => 'ready',
        ]);

        $backup = Backup::create([
            'type' => BackupType::DATABASE,
            'server_id' => $server->id,
            'storage_id' => $storage->id,
            'database_id' => $database->id,
            'interval' => '0 0 * * *',
            'keep_backups' => 5,
            'status' => 'running',
        ]);

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals(BackupType::DATABASE, $backup->type);
        $this->assertEquals($database->id, $backup->database_id);
        $this->assertNull($backup->path);
        $this->assertEquals($storage->id, $backup->storage_id);
    }
}
