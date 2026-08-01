<?php

use App\Facades\FTP;
use App\Facades\SFTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use App\Models\User;
use App\StorageProviders\Dropbox;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('create', function (array $input) {
    $this->actingAs($this->user);

    if ($input['provider'] === App\StorageProviders\FTP::id()) {
        FTP::fake();
    }

    if ($input['provider'] === App\StorageProviders\SFTP::id()) {
        SFTP::fake();
    }

    $this->post(route('storage-providers.store'), $input)
        ->assertSessionDoesntHaveErrors();

    if ($input['provider'] === App\StorageProviders\FTP::id()) {
        FTP::assertConnected($input['host']);
    }

    if ($input['provider'] === App\StorageProviders\SFTP::id()) {
        SFTP::assertConnected($input['host']);
    }

    $this->assertDatabaseHas('storage_providers', [
        'provider' => $input['provider'],
        'profile' => $input['name'],
        'project_id' => isset($input['global']) ? null : $this->user->current_project_id,
    ]);
})->with('createData');

test('dropbox oauth redirect', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('storage-providers.dropbox.redirect'), [
        'provider' => Dropbox::id(),
        'name' => 'dropbox-test',
        'app_key' => 'my-app-key',
        'app_secret' => 'my-app-secret',
    ]);

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');
    $this->assertStringContainsString('dropbox.com/oauth2/authorize', $location);
    $this->assertStringContainsString('token_access_type=offline', $location);
    $this->assertStringContainsString('my-app-key', $location);
    expect(session('dropbox_oauth.state'))->not->toBeEmpty();
});

test('dropbox callback creates provider', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
            'refresh_token' => 'the-refresh-token',
        ]),
        '*' => Http::response([], 200),
    ]);

    $response = $this->withSession([
        'dropbox_oauth' => [
            'state' => 'state-123',
            'name' => 'dropbox-test',
            'app_key' => 'my-app-key',
            'app_secret' => 'my-app-secret',
            'global' => false,
        ],
    ])->get(route('storage-providers.dropbox.callback', ['code' => 'auth-code', 'state' => 'state-123']));

    $response->assertRedirect(route('storage-providers'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('storage_providers', [
        'provider' => Dropbox::id(),
        'profile' => 'dropbox-test',
        'user_id' => $this->user->id,
    ]);

    $provider = StorageProviderModel::query()->where('profile', 'dropbox-test')->firstOrFail();
    expect($provider->credentials['refresh_token'])->toBe('the-refresh-token');
    expect($provider->credentials['app_key'])->toBe('my-app-key');
});

test('dropbox callback rejects bad state', function () {
    $this->actingAs($this->user);

    $response = $this->withSession([
        'dropbox_oauth' => [
            'state' => 'correct-state',
            'name' => 'dropbox-test',
            'app_key' => 'my-app-key',
            'app_secret' => 'my-app-secret',
            'global' => false,
        ],
    ])->get(route('storage-providers.dropbox.callback', ['code' => 'auth-code', 'state' => 'wrong-state']));

    $response->assertRedirect(route('storage-providers'));
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('storage_providers', ['profile' => 'dropbox-test']);
});

test('dropbox callback rejects missing code', function () {
    $this->actingAs($this->user);

    $response = $this->withSession([
        'dropbox_oauth' => [
            'state' => 'state-123',
            'name' => 'dropbox-test',
            'app_key' => 'my-app-key',
            'app_secret' => 'my-app-secret',
            'global' => false,
        ],
    ])->get(route('storage-providers.dropbox.callback', ['state' => 'state-123']));

    $response->assertRedirect(route('storage-providers'));
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('storage_providers', ['profile' => 'dropbox-test']);
});

test('see providers list', function () {
    $this->actingAs($this->user);

    StorageProviderModel::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $this->get(route('storage-providers'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('storage-providers/index'));
});

test('delete provider', function () {
    $this->actingAs($this->user);

    $provider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->delete(route('storage-providers.destroy', ['storageProvider' => $provider->id]));

    $this->assertDatabaseMissing('storage_providers', [
        'id' => $provider->id,
    ]);
});

test('cannot delete provider', function () {
    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $provider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $provider->id,
    ]);

    $this->delete(route('storage-providers.destroy', ['storageProvider' => $provider->id]))
        ->assertSessionHasErrors([
            'provider' => 'This storage provider is being used by a backup.',
        ]);

    $this->assertDatabaseHas('storage_providers', [
        'id' => $provider->id,
    ]);
});

test('user cannot update other users storage provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'hacked',
    ])
        ->assertForbidden();
});

test('user cannot delete other users storage provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->delete(route('storage-providers.destroy', $storageProvider))
        ->assertForbidden();
});

test('guest cannot access storage providers', function () {
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->get(route('storage-providers'))
        ->assertRedirect('/');

    $this->post(route('storage-providers.store'), [])
        ->assertRedirect('/');

    $this->patch(route('storage-providers.update', $storageProvider), [])
        ->assertRedirect('/');

    $this->delete(route('storage-providers.destroy', $storageProvider))
        ->assertRedirect('/');
});

test('cannot manipulate user id on creation', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();

    $this->post(route('storage-providers.store'), [
        'provider' => Local::id(),
        'name' => 'test',
        'path' => '/home/test',
        'user_id' => $otherUser->id,
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

test('cannot transfer ownership via update', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'original',
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'user_id' => $otherUser->id,
    ]);

    $storageProvider->refresh();

    expect($storageProvider->user_id)->toEqual($this->user->id);
    $this->assertNotEquals($otherUser->id, $storageProvider->user_id);
});

test('user can only see own storage providers in list', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();

    $ownProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'own-provider',
    ]);

    $otherProvider = StorageProviderModel::factory()->create([
        'user_id' => $otherUser->id,
        'profile' => 'other-provider',
    ]);

    $response = $this->get(route('storage-providers'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('storage-providers/index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page->has('storageProviders.data')
        ->where('storageProviders.data.0.id', $ownProvider->id)
        ->whereNot('storageProviders.data.0.id', $otherProvider->id)
    );
});

dataset('createData', /** @return array<int, array{0: array<string, mixed>}> */ function (): array {
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
                'provider' => App\StorageProviders\SFTP::id(),
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
                'provider' => App\StorageProviders\SFTP::id(),
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
