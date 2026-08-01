<?php

use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\Dropbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('access token refreshes from refresh token', function () {
    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
        ]),
    ]);

    $storageProvider = StorageProviderModel::factory()->dropbox()->create();

    $provider = $storageProvider->provider();
    expect($provider)->toBeInstanceOf(Dropbox::class);
    expect($provider->accessToken())->toBe('fresh-access');
});

test('access token throws when credentials incomplete', function () {
    $storageProvider = StorageProviderModel::factory()->create([
        'provider' => Dropbox::id(),
        'credentials' => [
            'token' => 'legacy-token',
        ],
    ]);

    $provider = $storageProvider->provider();
    expect($provider)->toBeInstanceOf(Dropbox::class);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Dropbox credentials are incomplete, please reconnect.');

    $provider->accessToken();
});
