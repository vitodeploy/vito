<?php

namespace Tests\Feature;

use App\Actions\VitoBackup\RunVitoBackup;
use App\Enums\VitoBackupFileStatus;
use App\Enums\VitoBackupStatus;
use App\Http\Resources\VitoBackupFileResource;
use App\Http\Resources\VitoBackupResource;
use App\Models\StorageProvider;
use App\Models\VitoBackup;
use App\Models\VitoBackupFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VitoBackupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_vito_backups_page(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('vito-backups.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('vito-settings/backups/index')
                ->has('vitoBackups', 1)
                ->has('storageProviders')
                ->where('vitoBackups.0.id', $vitoBackup->id)
                ->where('vitoBackups.0.name', $vitoBackup->name)
            );
    }

    public function test_store_creates_vito_backup_successfully(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $input = [
            'frequency' => '0 0 * * *',
            'keep_backups' => 5,
            'storage_id' => $storageProvider->id,
        ];

        $this->actingAs($this->user)
            ->post(route('vito-backups.store'), $input)
            ->assertRedirect(route('vito-backups.index'))
            ->assertSessionHas('success', 'Vito backup created successfully.');

        $this->assertDatabaseHas('vito_backups', [
            'name' => 'Daily Vito Backup',
            'frequency' => '0 0 * * *',
            'keep_backups' => 5,
            'storage_id' => $storageProvider->id,
            'status' => VitoBackupStatus::PENDING->value,
        ]);
    }

    public function test_store_returns_validation_errors_on_invalid_input(): void
    {
        $input = [
            'frequency' => 'invalid-cron',
            'keep_backups' => 150,
            'storage_id' => 999,
        ];

        $this->actingAs($this->user)
            ->post(route('vito-backups.store'), $input)
            ->assertRedirect()
            ->assertSessionHasErrors(['frequency', 'keep_backups', 'storage_id']);
    }

    public function test_update_modifies_vito_backup_successfully(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $input = [
            'frequency' => '0 * * * *',
            'keep_backups' => 10,
        ];

        $this->actingAs($this->user)
            ->put(route('vito-backups.update', $vitoBackup), $input)
            ->assertRedirect(route('vito-backups.index'))
            ->assertSessionHas('success', 'Vito backup updated successfully.');

        $vitoBackup->refresh();

        $this->assertEquals('Hourly Vito Backup', $vitoBackup->name);
        $this->assertEquals('0 * * * *', $vitoBackup->frequency);
        $this->assertEquals(10, $vitoBackup->keep_backups);
    }

    public function test_update_returns_validation_errors_on_invalid_input(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $input = [
            'frequency' => 'invalid-cron',
            'keep_backups' => 0,
        ];

        $this->actingAs($this->user)
            ->put(route('vito-backups.update', $vitoBackup), $input)
            ->assertRedirect()
            ->assertSessionHasErrors(['frequency', 'keep_backups']);
    }

    public function test_destroy_deletes_vito_backup_successfully(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('vito-backups.destroy', $vitoBackup))
            ->assertRedirect(route('vito-backups.index'))
            ->assertSessionHas('success', 'Vito backup deleted successfully.');

        $this->assertDatabaseMissing('vito_backups', ['id' => $vitoBackup->id]);
    }

    public function test_run_starts_backup_successfully(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $this->mock(RunVitoBackup::class, function ($mock) use ($vitoBackup) {
            $mock->shouldReceive('run')
                ->with(\Mockery::on(function ($backup) use ($vitoBackup) {
                    return $backup->id === $vitoBackup->id;
                }))
                ->once();
        });

        $this->actingAs($this->user)
            ->post(route('vito-backups.run', $vitoBackup))
            ->assertRedirect(route('vito-backups.index'))
            ->assertSessionHas('success', 'Vito backup started successfully.');
    }

    public function test_run_handles_backup_failure(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $this->mock(RunVitoBackup::class, function ($mock) use ($vitoBackup) {
            $mock->shouldReceive('run')
                ->with(\Mockery::on(function ($backup) use ($vitoBackup) {
                    return $backup->id === $vitoBackup->id;
                }))
                ->once()
                ->andThrow(new \Exception('Backup failed'));
        });

        $this->actingAs($this->user)
            ->post(route('vito-backups.run', $vitoBackup))
            ->assertRedirect(route('vito-backups.index'))
            ->assertSessionHas('error', 'Failed to start backup: Backup failed');
    }

    public function test_index_includes_storage_providers(): void
    {
        $s3Provider = StorageProvider::factory()->create([
            'provider' => 's3',
            'profile' => 'S3 Provider',
            'project_id' => null,
        ]);

        $dropboxProvider = StorageProvider::factory()->create([
            'provider' => 'dropbox',
            'profile' => 'Dropbox Provider',
            'project_id' => null,
        ]);

        $ftpProvider = StorageProvider::factory()->create([
            'provider' => 'ftp',
            'profile' => 'FTP Provider',
            'project_id' => null,
        ]);

        // Create a provider that should not be included (not in supported list)
        StorageProvider::factory()->create([
            'provider' => 'local',
            'project_id' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('vito-backups.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('storageProviders', 3)
                ->where('storageProviders.0.provider', 'dropbox')
                ->where('storageProviders.1.provider', 'ftp')
                ->where('storageProviders.2.provider', 's3')
            );
    }

    // HTTP Resource Tests
    public function test_vito_backup_resource_transforms_correctly(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'profile' => 'Test S3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'name' => 'Test Backup',
            'frequency' => '0 0 * * *',
            'keep_backups' => 5,
            'storage_id' => $storageProvider->id,
        ]);

        $resource = new VitoBackupResource($vitoBackup);
        $array = $resource->toArray(request());

        $this->assertEquals($vitoBackup->id, $array['id']);
        $this->assertEquals('Test Backup', $array['name']);
        $this->assertEquals('0 0 * * *', $array['frequency']);
        $this->assertEquals(5, $array['keep_backups']);
        $this->assertEquals($vitoBackup->status, $array['status']);
        $this->assertEquals($vitoBackup->created_at, $array['created_at']);
        $this->assertEquals($vitoBackup->updated_at, $array['updated_at']);
    }

    public function test_vito_backup_resource_includes_storage_when_loaded(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'profile' => 'Test S3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $vitoBackup->load('storage');

        $resource = new VitoBackupResource($vitoBackup);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('storage', $array);
        $this->assertEquals($storageProvider->id, $array['storage']['id']);
        $this->assertEquals('Test S3', $array['storage']['profile']);
        $this->assertEquals('s3', $array['storage']['provider']);
    }

    public function test_vito_backup_resource_includes_files_when_loaded(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $file1 = VitoBackupFile::factory()->create([
            'vito_backup_id' => $vitoBackup->id,
            'name' => 'backup1.zip',
        ]);

        $file2 = VitoBackupFile::factory()->create([
            'vito_backup_id' => $vitoBackup->id,
            'name' => 'backup2.zip',
        ]);

        $vitoBackup->load('files');

        $resource = new VitoBackupResource($vitoBackup);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('files', $array);
        $this->assertCount(2, $array['files']);
        $this->assertEquals($file1->id, $array['files'][0]['id']);
        $this->assertEquals($file2->id, $array['files'][1]['id']);
    }

    public function test_vito_backup_file_resource_transforms_correctly(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $backupFile = VitoBackupFile::factory()->create([
            'vito_backup_id' => $vitoBackup->id,
            'name' => 'test-backup.zip',
            'size' => 1024000,
            'status' => VitoBackupFileStatus::CREATED,
            'path' => 'vito-backups/test-backup.zip',
        ]);

        $resource = new VitoBackupFileResource($backupFile);
        $array = $resource->toArray(request());

        $this->assertEquals($backupFile->id, $array['id']);
        $this->assertEquals('test-backup.zip', $array['name']);
        $this->assertEquals(1024000, $array['size']);
        $this->assertEquals($backupFile->status, $array['status']);
        $this->assertEquals('vito-backups/test-backup.zip', $array['path']);
        $this->assertEquals($backupFile->created_at, $array['created_at']);
        $this->assertEquals($backupFile->updated_at, $array['updated_at']);
    }

    public function test_vito_backup_file_resource_handles_null_path(): void
    {
        $storageProvider = StorageProvider::factory()->create([
            'provider' => 's3',
            'project_id' => null,
        ]);

        $vitoBackup = VitoBackup::factory()->create([
            'storage_id' => $storageProvider->id,
        ]);

        $backupFile = VitoBackupFile::factory()->create([
            'vito_backup_id' => $vitoBackup->id,
            'path' => null,
        ]);

        $resource = new VitoBackupFileResource($backupFile);
        $array = $resource->toArray(request());

        $this->assertNull($array['path']);
    }
}
