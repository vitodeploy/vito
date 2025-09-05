<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File as FileSystem;
use ZipArchive;

class VitoSettingsImportTest extends BaseTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Minimal setup
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
        $this->user->createDefaultProject();
        
        // Create tmp directory if it doesn't exist
        if (!is_dir(storage_path('tmp'))) {
            mkdir(storage_path('tmp'), 0755, true);
        }
    }

    public function test_import_backup_with_valid_zip_file(): void
    {
        $this->actingAs($this->user);

        $zipFile = $this->createValidBackupZip();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile
        ]);

        $response->assertRedirect(route('vito-settings'))
                ->assertSessionHas('success', 'Settings imported successfully.');
    }

    public function test_import_backup_rejects_invalid_file_type(): void
    {
        $this->actingAs($this->user);

        $invalidFile = UploadedFile::fake()->create('backup.txt', 100);

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $invalidFile
        ]);

        $response->assertSessionHasErrors('backup_file');
    }

    public function test_import_backup_rejects_zip_without_database(): void
    {
        $this->actingAs($this->user);

        $zipFile = $this->createInvalidBackupZip();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile
        ]);

        $response->assertRedirect(route('vito-settings'))
                ->assertSessionHas('error')
                ->assertSessionHasErrors('backup_file');
    }

    public function test_import_backup_handles_multiple_database_locations(): void
    {
        $this->actingAs($this->user);

        $zipFile = $this->createBackupZipWithDatabaseInRoot();

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile
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
            'backup_file' => $zipFile
        ]);

        $response->assertRedirect()
                ->assertSessionHas('error', 'Import is disabled in demo mode.');
    }

    public function test_import_backup_validates_mime_types(): void
    {
        $this->actingAs($this->user);

        $zipFile = UploadedFile::fake()->createWithContent(
            'backup.zip',
            $this->createZipContent()
        )->mimeType('application/x-zip-compressed');

        $response = $this->post(route('vito-settings.import'), [
            'backup_file' => $zipFile
        ]);

        $response->assertRedirect(route('vito-settings'));
    }

    private function createValidBackupZip(): UploadedFile
    {
        $zipPath = storage_path('tmp/test-backup.zip');
        
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        
        $zip->addFromString('database.sqlite', 'fake_db_content');
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
        
        $zip->addFromString('.env', 'APP_ENV=production');
        
        $zip->close();

        return new UploadedFile($zipPath, 'invalid-backup.zip', 'application/zip', null, true);
    }

    private function createBackupZipWithDatabaseInRoot(): UploadedFile
    {
        $zipPath = storage_path('tmp/root-db-backup.zip');
        
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        
        $zip->addFromString('database.sqlite', 'fake_db_content');
        $zip->addFromString('.env', 'APP_ENV=production');
        
        $zip->close();

        return new UploadedFile($zipPath, 'root-db-backup.zip', 'application/zip', null, true);
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