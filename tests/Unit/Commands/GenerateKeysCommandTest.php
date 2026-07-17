<?php

namespace Tests\Unit\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateKeysCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_keys_when_storage_path_contains_spaces(): void
    {
        $storagePath = storage_path('framework/testing/key path with spaces');

        File::deleteDirectory($storagePath);
        File::makeDirectory($storagePath, recursive: true);

        $this->app->useStoragePath($storagePath);

        $this->artisan('ssh-key:generate', ['--force' => true])
            ->assertSuccessful();

        $this->assertFileExists($storagePath.'/ssh-private.pem');
        $this->assertFileExists($storagePath.'/ssh-public.key');
        $this->assertNotEmpty(file_get_contents($storagePath.'/ssh-public.key'));

        File::deleteDirectory($storagePath);
    }
}
