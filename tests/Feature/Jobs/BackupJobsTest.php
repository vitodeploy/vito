<?php

namespace Tests\Feature\Jobs;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Facades\SSH;
use App\Jobs\Backup\DeleteFileJob;
use App\Jobs\Backup\DeleteJob;
use App\Jobs\Backup\RestoreDatabaseJob;
use App\Jobs\Backup\RestoreFileJob;
use App\Jobs\Backup\RunJob;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\StorageProvider;
use App\StorageProviders\Dropbox;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupJobsTest extends TestCase
{
    use RefreshDatabase;

    protected StorageProvider $storageProvider;

    protected Backup $backup;

    protected BackupFile $backupFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageProvider = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
        ]);

        $this->backup = Backup::factory()->create([
            'type' => BackupType::DATABASE,
            'server_id' => $this->server->id,
            'storage_id' => $this->storageProvider->id,
            'database_id' => Database::factory()->create(['server_id' => $this->server->id])->id,
            'status' => BackupStatus::RUNNING,
        ]);

        $this->backupFile = BackupFile::factory()->create([
            'backup_id' => $this->backup->id,
            'status' => BackupFileStatus::CREATING,
        ]);
    }

    public function test_run_job_failed_sets_backup_and_file_to_failed_and_logs(): void
    {
        $job = new RunJob($this->backupFile, $this->backup);
        $job->failed(new Exception('Backup failed'));

        $this->backup->refresh();
        $this->backupFile->refresh();

        $this->assertEquals(BackupStatus::FAILED, $this->backup->status);
        $this->assertEquals(BackupFileStatus::FAILED, $this->backupFile->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'run-backup-failed',
        ]);
    }

    public function test_restore_database_job_failed_sets_restore_failed_and_logs(): void
    {
        $database = Database::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->backupFile->update(['status' => BackupFileStatus::RESTORING]);

        $job = new RestoreDatabaseJob($this->backupFile, $database);
        $job->failed(new Exception('Restore failed'));

        $this->backupFile->refresh();

        $this->assertEquals(BackupFileStatus::RESTORE_FAILED, $this->backupFile->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'restore-database-failed',
        ]);
    }

    public function test_restore_file_job_failed_sets_restore_failed_and_logs(): void
    {
        SSH::fake();

        $fileBackup = Backup::factory()->create([
            'type' => BackupType::FILE,
            'server_id' => $this->server->id,
            'storage_id' => $this->storageProvider->id,
            'path' => '/home/vito/app',
            'status' => BackupStatus::RUNNING,
        ]);

        $file = BackupFile::factory()->create([
            'backup_id' => $fileBackup->id,
            'status' => BackupFileStatus::RESTORING,
        ]);

        $job = new RestoreFileJob($file, '/home/vito/restored', 'vito:vito', '755');
        $job->failed(new Exception('Restore failed'));

        $file->refresh();

        $this->assertEquals(BackupFileStatus::RESTORE_FAILED, $file->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'restore-file-failed',
        ]);
    }

    public function test_delete_job_failed_logs(): void
    {
        $job = new DeleteJob($this->backup);
        $job->failed(new Exception('Delete failed'));

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'delete-backup-failed',
        ]);
    }

    public function test_delete_file_job_failed_logs(): void
    {
        $job = new DeleteFileJob($this->backupFile);
        $job->failed(new Exception('Delete file failed'));

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'delete-backup-file-failed',
        ]);
    }
}
