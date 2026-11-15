<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('export settings', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('vito-settings.export'))
        ->assertDownload('vito-backup-'.date('Y-m-d').'.zip');

    expect(is_dir(dirname($response->baseResponse->getFile()->getPathname())))->toBeFalse();
});

test('export archives the active environment file as .env', function () {
    $root = sys_get_temp_dir().'/vito-export-test-'.Str::random(8);
    File::makeDirectory($root);
    File::put($root.'/.env', 'APP_KEY=default');
    File::put($root.'/.env.production', 'APP_KEY=active');
    app()->useEnvironmentPath($root);
    app()->loadEnvironmentFrom('.env.production');
    $this->actingAs($this->user);

    $download = tempnam(sys_get_temp_dir(), 'vito-export-');
    $archive = null;
    app()->terminating(function () use (&$archive, $download): void {
        $archive = collect(glob(sys_get_temp_dir().'/vito-*/vito-backup-*.zip') ?: [])->sortByDesc(fn (string $file): int => filemtime($file))->first();
        File::copy($archive, $download);
    });

    try {
        $this->get(route('vito-settings.export'))->assertDownload();

        $zip = new ZipArchive;
        $zip->open($download);
        expect($zip->getFromName('.env'))->toBe('APP_KEY=active');
        $zip->close();
        expect(File::exists($archive))->toBeFalse();
    } finally {
        app()->useEnvironmentPath(base_path());
        app()->loadEnvironmentFrom('.env');
        File::deleteDirectory($root);
        File::delete($download);
    }
});

test('export is disabled in demo mode', function () {
    config(['app.demo' => true]);
    $this->actingAs($this->user);

    $this->get(route('vito-settings.export'))->assertForbidden();
});

test('import installs secrets with owner-only permissions', function () {
    $originalStoragePath = storage_path();
    $root = sys_get_temp_dir().'/vito-import-test-'.Str::random(8);
    File::makeDirectory($root.'/storage/app', 0755, true);
    File::copy(storage_path(config('core.ssh_public_key_name')), $root.'/storage/'.config('core.ssh_public_key_name'));
    app()->useStoragePath($root.'/storage');
    app()->useEnvironmentPath($root);
    Artisan::shouldReceive('call')->once()->with('optimize');

    $zipPath = $root.'/backup.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    foreach ([
        'database.sqlite' => 'db',
        '.env' => 'APP_KEY=test',
        'ssh-public.key' => 'public',
        'ssh-private.pem' => 'private',
        'key-pairs/1' => 'key',
        'key-pairs/1.pub' => 'key.pub',
        'server-logs/1-test.log' => 'log',
    ] as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();

    try {
        $this->actingAs($this->user)
            ->post(route('vito-settings.import'), [
                'backup_file' => new UploadedFile($zipPath, 'backup.zip', 'application/zip', null, true),
            ])
            ->assertRedirect(route('vito-settings'));

        $mode = fn (string $path): int => fileperms($path) & 0777;

        expect(file_get_contents($root.'/.env'))->toBe('APP_KEY=test');
        foreach (['.env', 'storage/database.sqlite', 'storage/ssh-private.pem', 'storage/app/key-pairs/1', 'storage/app/key-pairs/1.pub', 'storage/app/server-logs/1-test.log'] as $file) {
            expect($mode($root.'/'.$file))->toBe(0600, $file);
        }
        foreach (['storage/app/key-pairs', 'storage/app/server-logs'] as $directory) {
            expect($mode($root.'/'.$directory))->toBe(0700, $directory);
        }
    } finally {
        app()->useStoragePath($originalStoragePath);
        app()->useEnvironmentPath(base_path());
        File::deleteDirectory($root);
    }
});
