<?php

use App\Facades\FTP;
use App\Facades\SFTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use App\Models\User;
use App\StorageProviders\Dropbox;
use App\StorageProviders\Local;
use App\StorageProviders\S3;
use App\StorageProviders\SFTP as SFTPProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create', function (array $input) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    if ($input['provider'] === Dropbox::id()) {
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
            '*' => Http::response([], 200),
        ]);
    }

    if ($input['provider'] === App\StorageProviders\FTP::id()) {
        FTP::fake();
    }

    if ($input['provider'] === SFTPProvider::id()) {
        SFTP::fake();
    }

    $this->json('POST', route('api.projects.storage-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $input)
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => $input['provider'],
            'name' => $input['name'],
        ]);
})->with('createData');

test('see providers list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var StorageProviderModel $provider */
    $provider = StorageProviderModel::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $this->json('GET', route('api.projects.storage-providers', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => $provider->provider,
            'name' => $provider->profile,
        ]);
});

test('delete provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var StorageProviderModel $provider */
    $provider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->json('DELETE', route('api.projects.storage-providers.delete', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $provider->id,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
});

test('cannot delete provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Database $database */
    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    /** @var StorageProviderModel $provider */
    $provider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $provider->id,
    ]);

    $this->json('DELETE', route('api.projects.storage-providers.delete', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $provider->id,
    ]))
        ->assertJsonValidationErrorFor('provider');
});

test('api editable data excludes secrets', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => S3::id(),
        'credentials' => [
            'api_url' => 'https://s3.amazonaws.com',
            'key' => 'test-key',
            'secret' => 'super-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
            'path' => '/backups',
        ],
    ]);

    $this->json('GET', route('api.projects.storage-providers.show', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]))
        ->assertOk()
        ->assertJsonPath('editable_data.bucket', 'test-bucket')
        ->assertJsonMissingPath('editable_data.secret')
        ->assertDontSee('super-secret');
});

test('api read only token cannot read editable data', function () {
    Sanctum::actingAs($this->user, ['read']);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => S3::id(),
        'credentials' => [
            'api_url' => 'https://s3.amazonaws.com',
            'key' => 'test-key',
            'secret' => 'super-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
            'path' => '/backups',
        ],
    ]);

    $this->json('GET', route('api.projects.storage-providers.show', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]))
        ->assertOk()
        ->assertJsonPath('editable_data', [])
        ->assertDontSee('test-bucket');
});

test('api show survives a provider with no registered handler', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => 'removed-plugin-provider',
        'credentials' => ['key' => 'value'],
    ]);

    $this->json('GET', route('api.projects.storage-providers.show', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]))
        ->assertOk()
        ->assertJsonPath('editable_data', []);
});

test('api update merges credentials without exposing secrets', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SFTP::fake();

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => SFTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
        ],
    ]);

    $this->json('PUT', route('api.projects.storage-providers.update', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]), [
        'name' => 'updated',
        'host' => '5.6.7.8',
    ])
        ->assertOk();

    $storageProvider->refresh();

    expect($storageProvider->credentials['host'])->toBe('5.6.7.8')
        ->and($storageProvider->credentials['password'])->toBe('original-password');
});

test('api user cannot update other users storage provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('PUT', route('api.projects.storage-providers.update', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]), [
        'name' => 'hacked',
    ])
        ->assertForbidden();
});

test('api user cannot delete other users storage provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('DELETE', route('api.projects.storage-providers.delete', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]))
        ->assertForbidden();
});

test('api guest cannot access storage providers', function () {
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->json('GET', route('api.projects.storage-providers', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertUnauthorized();

    $this->json('POST', route('api.projects.storage-providers.create', [
        'project' => $this->user->current_project_id,
    ]), [])
        ->assertUnauthorized();

    $this->json('DELETE', route('api.projects.storage-providers.delete', [
        'project' => $this->user->current_project_id,
        'storageProvider' => $storageProvider->id,
    ]))
        ->assertUnauthorized();
});

test('api insufficient scopes denies access', function () {
    Sanctum::actingAs($this->user, ['read']);

    // Only read scope
    $data = [
        'provider' => Local::id(),
        'name' => 'test',
        'path' => '/home/test',
    ];

    $this->json('POST', route('api.projects.storage-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertForbidden();
});

test('api cannot manipulate user id on creation', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();

    $data = [
        'provider' => Local::id(),
        'name' => 'test',
        'path' => '/home/test',
        'user_id' => $otherUser->id,
    ];

    $this->json('POST', route('api.projects.storage-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => Local::id(),
            'name' => 'test',
        ]);

    $this->assertDatabaseHas('storage_providers', [
        'profile' => 'test',
        'provider' => Local::id(),
        'user_id' => $this->user->id,
    ]);

    $this->assertDatabaseMissing('storage_providers', [
        'profile' => 'test',
        'provider' => Local::id(),
        'user_id' => $otherUser->id,
    ]);
});

test('api user can only see own storage providers in list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();

    $ownProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'own-provider',
    ]);

    $otherProvider = StorageProviderModel::factory()->create([
        'user_id' => $otherUser->id,
        'profile' => 'other-provider',
    ]);

    $this->json('GET', route('api.projects.storage-providers', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $ownProvider->id,
            'provider' => $ownProvider->provider,
        ])
        ->assertJsonMissing([
            'id' => $otherProvider->id,
        ]);
});

/**
 * @return array<int, array<int, array<string, mixed>>>
 */
dataset('createData', function () {
    return [
        [
            [
                'provider' => Local::id(),
                'name' => 'local-test',
                'path' => '/home/vito/backups',
            ],
        ],
        [
            [
                'provider' => Local::id(),
                'name' => 'local-test',
                'path' => '/home/vito/backups',
                'global' => 1,
            ],
        ],
        [
            [
                'provider' => App\StorageProviders\FTP::id(),
                'name' => 'ftp-test',
                'host' => '1.2.3.4',
                'port' => '22',
                'path' => '/home/vito',
                'username' => 'username',
                'password' => 'password',
                'ssl' => 1,
                'passive' => 1,
            ],
        ],
        [
            [
                'provider' => App\StorageProviders\FTP::id(),
                'name' => 'ftp-test',
                'host' => '1.2.3.4',
                'port' => '22',
                'path' => '/home/vito',
                'username' => 'username',
                'password' => 'password',
                'ssl' => 1,
                'passive' => 1,
                'global' => 1,
            ],
        ],
        [
            [
                'provider' => Dropbox::id(),
                'name' => 'dropbox-test',
                'app_key' => 'app-key',
                'app_secret' => 'app-secret',
                'refresh_token' => 'refresh-token',
            ],
        ],
        [
            [
                'provider' => Dropbox::id(),
                'name' => 'dropbox-test',
                'app_key' => 'app-key',
                'app_secret' => 'app-secret',
                'refresh_token' => 'refresh-token',
                'global' => 1,
            ],
        ],
        [
            [
                'provider' => SFTPProvider::id(),
                'name' => 'sftp-test',
                'host' => '1.2.3.4',
                'port' => '22',
                'path' => '/home/vito',
                'username' => 'username',
                'password' => 'password',
            ],
        ],
        [
            [
                'provider' => SFTPProvider::id(),
                'name' => 'sftp-test',
                'host' => '1.2.3.4',
                'port' => '22',
                'path' => '/home/vito',
                'username' => 'username',
                'password' => 'password',
                'global' => 1,
            ],
        ],
    ];
});
