<?php

use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\Dropbox;
use GuzzleHttp\Psr7\Utils;
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

test('interrupted cloud downloads fail and close their stream', function () {
    $provider = Mockery::mock(Dropbox::class, [new StorageProviderModel])->makePartial()->shouldAllowMockingProtectedMethods();
    $body = Utils::streamFor('partial');
    $download = (new ReflectionMethod(Dropbox::class, 'httpDownload'))->invoke($provider, $body, 100);

    ob_start();
    try {
        expect($download['stream'])->toThrow(RuntimeException::class, 'The backup download was interrupted.');
    } finally {
        ob_end_clean();
    }
    expect($body->isReadable())->toBeFalse();
});
