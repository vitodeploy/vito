<?php

namespace Tests\Unit\StorageProviders;

use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\Dropbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DropboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_token_refreshes_from_refresh_token(): void
    {
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
        ]);

        $storageProvider = StorageProviderModel::factory()->dropbox()->create();

        $provider = $storageProvider->provider();
        $this->assertInstanceOf(Dropbox::class, $provider);
        $this->assertSame('fresh-access', $provider->accessToken());
    }

    public function test_access_token_throws_when_credentials_incomplete(): void
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'provider' => Dropbox::id(),
            'credentials' => [
                'token' => 'legacy-token',
            ],
        ]);

        $provider = $storageProvider->provider();
        $this->assertInstanceOf(Dropbox::class, $provider);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dropbox credentials are incomplete, please reconnect.');

        $provider->accessToken();
    }
}
