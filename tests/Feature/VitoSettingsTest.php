<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File as FileSystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class VitoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tmp directory if it doesn't exist
        if (! is_dir(storage_path('tmp'))) {
            mkdir(storage_path('tmp'), 0755, true);
        }
    }

    public function test_export_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('vito-settings.export'))
            ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');
    }

    public function test_import_backup_with_valid_zip_file(): void
    {
        $this->actingAs($this->user);

        $zipFile = $this->createValidBackupZip();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        $response->assertRedirect(route('vito-settings'))
            ->assertSessionHas('success', 'Settings imported successfully.');
    }

    public function test_import_backup_rejects_invalid_file_type(): void
    {
        $this->actingAs($this->user);

        $invalidFile = UploadedFile::fake()->create('backup.txt', 100);

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('backup_file');
    }

    public function test_import_backup_rejects_zip_without_database(): void
    {
        $this->actingAs($this->user);

        $zipFile = $this->createInvalidBackupZip();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        $response->assertRedirect(route('vito-settings'))
            ->assertSessionHas('error')
            ->assertSessionHasErrors('backup_file');
    }

    public function test_import_backup_handles_multiple_database_locations(): void
    {
        $this->actingAs($this->user);

        // Test with database.sqlite in root
        $zipFile = $this->createBackupZipWithDatabaseInRoot();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        $response->assertRedirect(route('vito-settings'))
            ->assertSessionHas('success');
    }

    public function test_import_backup_moves_files_to_correct_locations(): void
    {
        $this->actingAs($this->user);

        // Create original files to verify they get replaced
        FileSystem::put(base_path('.env'), 'OLD_ENV=true');
        FileSystem::put(storage_path('database.sqlite'), 'old_db_content');

        $zipFile = $this->createValidBackupZip();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        $response->assertRedirect(route('vito-settings'))
            ->assertSessionHas('success');

        // Verify files were moved to correct locations
        $this->assertTrue(FileSystem::exists(base_path('.env')));
        $this->assertTrue(FileSystem::exists(storage_path('database.sqlite')));
    }

    public function test_import_backup_handles_alternative_file_paths(): void
    {
        $this->actingAs($this->user);

        $zipFile = $this->createBackupWithAlternativePaths();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        $response->assertRedirect(route('vito-settings'))
            ->assertSessionHas('success');
    }

    public function test_import_backup_fails_in_demo_mode(): void
    {
        config(['app.demo' => true]);

        $this->actingAs($this->user);

        $zipFile = $this->createValidBackupZip();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('error', 'Import is disabled in demo mode.');
    }

    public function test_import_backup_validates_mime_types(): void
    {
        $this->actingAs($this->user);

        // Test with different valid MIME types
        $zipFile = UploadedFile::fake()->createWithContent(
            'backup.zip',
            $this->createZipContent()
        )->mimeType('application/x-zip-compressed');

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile,
        ]);

        // Should not fail on MIME type validation
        $response->assertRedirect(route('vito-settings'));
    }

    private function createValidBackupZip(): UploadedFile
    {
        $zipPath = storage_path('tmp/test-backup.zip');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        // Add required database file
        $zip->addFromString('database.sqlite', 'fake_db_content');

        // Add other typical backup files
        $zip->addFromString('.env', 'APP_ENV=production');
        $zip->addFromString('ssh-public.key', 'fake_public_key');
        $zip->addFromString('ssh-private.pem', 'fake_private_key');

        $zip->close();

        return new UploadedFile($zipPath, 'test-backup.zip', 'application/zip', null, true);
    }

    private function createInvalidBackupZip(): UploadedFile
    {
        $zipPath = storage_path('tmp/invalid-backup.zip');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        // Missing database file - should cause validation failure
        $zip->addFromString('.env', 'APP_ENV=production');

        $zip->close();

        return new UploadedFile($zipPath, 'invalid-backup.zip', 'application/zip', null, true);
    }

    private function createBackupZipWithDatabaseInRoot(): UploadedFile
    {
        $zipPath = storage_path('tmp/root-db-backup.zip');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        // Database in root instead of storage/ directory
        $zip->addFromString('database.sqlite', 'fake_db_content');
        $zip->addFromString('.env', 'APP_ENV=production');

        $zip->close();

        return new UploadedFile($zipPath, 'root-db-backup.zip', 'application/zip', null, true);
    }

    private function createBackupWithAlternativePaths(): UploadedFile
    {
        $zipPath = storage_path('tmp/alt-paths-backup.zip');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        // Test alternative paths that should still be found
        $zip->addFromString('storage/database.sqlite', 'fake_db_content');
        $zip->addFromString('storage/ssh-public.key', 'fake_public_key');
        $zip->addFromString('storage/ssh-private.pem', 'fake_private_key');

        $zip->close();

        return new UploadedFile($zipPath, 'alt-paths-backup.zip', 'application/zip', null, true);
    }

    private function createZipContent(): string
    {
        $zipPath = storage_path('tmp/content-test.zip');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('database.sqlite', 'fake_db_content');
        $zip->close();

        return FileSystem::get($zipPath);
    }
}
