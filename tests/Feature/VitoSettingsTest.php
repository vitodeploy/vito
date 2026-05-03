<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class VitoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('vito-settings.export'))
            ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');
    }

    public function test_import_keeps_session_driver_when_not_database(): void
    {
        $this->actingAs($this->user);
        config(['session.driver' => 'cookie']);
        Artisan::shouldReceive('call')->with('optimize')->once();

        $zipPath = $this->buildBackupZip();
        $snapshot = $this->snapshotImportTargets();

        try {
            $response = $this->post(route('vito-settings.import'), [
                'backup_file' => new UploadedFile($zipPath, 'backup.zip', 'application/zip', null, true),
            ]);

            $response->assertRedirect(route('vito-settings'));
            $response->assertSessionHas('success', 'Settings imported successfully.');
            $this->assertSame('cookie', config('session.driver'));
        } finally {
            $this->restoreImportTargets($snapshot);
            @unlink($zipPath);
        }
    }

    public function test_import_swaps_session_driver_to_file_when_database(): void
    {
        $this->actingAs($this->user);
        config(['session.driver' => 'database']);
        Artisan::shouldReceive('call')->with('optimize')->once();

        $zipPath = $this->buildBackupZip();
        $snapshot = $this->snapshotImportTargets();

        try {
            $response = $this->post(route('vito-settings.import'), [
                'backup_file' => new UploadedFile($zipPath, 'backup.zip', 'application/zip', null, true),
            ]);

            $response->assertRedirect(route('vito-settings'));
            $response->assertSessionHas('success', 'Settings imported successfully.');
            $this->assertSame('file', config('session.driver'));
        } finally {
            $this->restoreImportTargets($snapshot);
            @unlink($zipPath);
        }
    }

    private function buildBackupZip(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'vito-backup-test-');
        $zipPath = $tmp.'.zip';
        @unlink($tmp);

        $databasePath = storage_path('database.sqlite');
        $databaseContents = File::exists($databasePath)
            ? File::get($databasePath)
            : '';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('database.sqlite', $databaseContents);
        $zip->addFromString('ssh-public.key', 'test-public-key');
        $zip->addFromString('ssh-private.pem', 'test-private-pem');
        $zip->close();

        return $zipPath;
    }

    /**
     * @return array<string, string|null>
     */
    private function snapshotImportTargets(): array
    {
        $paths = [
            storage_path('database.sqlite'),
            storage_path('ssh-public.key'),
            storage_path('ssh-private.pem'),
        ];

        $snapshot = [];
        foreach ($paths as $path) {
            $snapshot[$path] = File::exists($path) ? File::get($path) : null;
        }

        return $snapshot;
    }

    /**
     * @param  array<string, string|null>  $snapshot
     */
    private function restoreImportTargets(array $snapshot): void
    {
        foreach ($snapshot as $path => $content) {
            if ($content === null) {
                if (File::exists($path)) {
                    File::delete($path);
                }

                continue;
            }

            File::put($path, $content);
        }
    }
}
