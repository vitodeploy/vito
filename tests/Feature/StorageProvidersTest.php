<?php

use App\Enums\UserRole;
use App\Facades\FTP;
use App\Facades\SFTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\Project;
use App\Models\StorageProvider as StorageProviderModel;
use App\Models\User;
use App\StorageProviders\Dropbox;
use App\StorageProviders\FTP as FTPProvider;
use App\StorageProviders\Local;
use App\StorageProviders\S3;
use App\StorageProviders\SFTP as SFTPProvider;
use App\Support\Testing\FTPFake;
use App\Support\Testing\SFTPFake;
use FTP\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('create', function (array $input) {
    $this->actingAs($this->user);

    if ($input['provider'] === FTPProvider::id()) {
        FTP::fake();
    }

    if ($input['provider'] === SFTPProvider::id()) {
        SFTP::fake();
    }

    $this->post(route('storage-providers.store'), $input)
        ->assertSessionDoesntHaveErrors();

    if ($input['provider'] === FTPProvider::id()) {
        FTP::assertConnected($input['host']);
    }

    if ($input['provider'] === SFTPProvider::id()) {
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

test('dropbox oauth redirect resolves the target project before leaving for dropbox', function (mixed $global, bool $expectGlobal) {
    $this->actingAs($this->user);

    $this->post(route('storage-providers.dropbox.redirect'), [
        'provider' => Dropbox::id(),
        'name' => 'dropbox-test',
        'app_key' => 'my-app-key',
        'app_secret' => 'my-app-secret',
        'global' => $global,
    ])->assertRedirect();

    expect(session('dropbox_oauth.project_id'))
        ->toBe($expectGlobal ? null : $this->user->current_project_id);
})->with([
    'string false stays project scoped' => ['false', false],
    'string off stays project scoped' => ['off', false],
    'zero stays project scoped' => ['0', false],
    'true goes global' => [true, true],
    'string one goes global' => ['1', true],
]);

test('dropbox callback keeps the project chosen at redirect time', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
            'refresh_token' => 'the-refresh-token',
        ]),
        '*' => Http::response([], 200),
    ]);

    $chosenProject = $this->user->current_project_id;

    /** @var Project $otherProject */
    $otherProject = Project::factory()->create();
    $otherProject->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);
    $this->user->update(['current_project_id' => $otherProject->id]);

    $this->withSession([
        'dropbox_oauth' => [
            'state' => 'state-123',
            'name' => 'dropbox-switched',
            'app_key' => 'my-app-key',
            'app_secret' => 'my-app-secret',
            'project_id' => $chosenProject,
        ],
    ])->get(route('storage-providers.dropbox.callback', ['code' => 'auth-code', 'state' => 'state-123']))
        ->assertRedirect(route('storage-providers'));

    $this->assertDatabaseHas('storage_providers', [
        'profile' => 'dropbox-switched',
        'project_id' => $chosenProject,
    ]);
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
            'project_id' => $this->user->current_project_id,
        ],
    ])->get(route('storage-providers.dropbox.callback', ['code' => 'auth-code', 'state' => 'state-123']));

    $response->assertRedirect(route('storage-providers'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('storage_providers', [
        'provider' => Dropbox::id(),
        'profile' => 'dropbox-test',
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
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

test('update keeps credentials when nothing is changed', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => S3::id(),
        'credentials' => [
            'api_url' => 'https://s3.amazonaws.com',
            'key' => 'original-key',
            'secret' => 'original-secret',
            'region' => 'us-east-1',
            'bucket' => 'original-bucket',
            'path' => '/backups',
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'api_url' => 'https://s3.amazonaws.com',
        'key' => 'original-key',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => 'original-bucket',
        'path' => '/backups',
    ])
        ->assertSessionDoesntHaveErrors();

    $storageProvider->refresh();

    expect($storageProvider->profile)->toBe('updated')
        ->and($storageProvider->credentials['secret'])->toBe('original-secret')
        ->and($storageProvider->credentials['bucket'])->toBe('original-bucket');
});

test('update changes non secret credentials and reconnects', function () {
    $this->actingAs($this->user);

    SFTP::fake();

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => SFTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'host' => '5.6.7.8',
        'port' => 22,
        'path' => '/home/vito',
        'username' => 'username',
        'password' => '',
    ])
        ->assertSessionDoesntHaveErrors();

    SFTP::assertConnected('5.6.7.8');

    $storageProvider->refresh();

    expect($storageProvider->credentials['host'])->toBe('5.6.7.8')
        ->and($storageProvider->credentials['password'])->toBe('original-password');
});

test('update stores a new secret when one is provided', function () {
    $this->actingAs($this->user);

    SFTP::fake();

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => SFTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'password' => 'new-password',
    ])
        ->assertSessionDoesntHaveErrors();

    $storageProvider->refresh();

    expect($storageProvider->credentials['password'])->toBe('new-password')
        ->and($storageProvider->credentials['host'])->toBe('1.2.3.4');
});

test('update rejects credentials that fail to connect', function () {
    $this->actingAs($this->user);

    SFTP::swap(new class extends SFTPFake
    {
        public function connect(string $host, int $port, string $username, string $password): bool
        {
            return false;
        }
    });

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => SFTPProvider::id(),
        'profile' => 'original',
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'password' => 'bad-password',
    ])
        ->assertSessionHasErrors('provider');

    $storageProvider->refresh();

    expect($storageProvider->credentials['password'])->toBe('original-password')
        ->and($storageProvider->profile)->toBe('original');
});

test('update rejects blanking a required credential', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => S3::id(),
        'credentials' => [
            'api_url' => 'https://s3.amazonaws.com',
            'key' => 'original-key',
            'secret' => 'original-secret',
            'region' => 'us-east-1',
            'bucket' => 'original-bucket',
            'path' => '/backups',
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'bucket' => '',
    ])
        ->assertSessionHasErrors('bucket');

    $storageProvider->refresh();

    expect($storageProvider->credentials['bucket'])->toBe('original-bucket');
});

test('update rejects blanking a required boolean credential', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => FTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 21,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
            'ssl' => true,
            'passive' => true,
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'ssl' => '',
    ])
        ->assertSessionHasErrors('ssl');

    $storageProvider->refresh();

    expect($storageProvider->credentials['ssl'])->toBeTrue();
});

test('update can toggle a boolean credential off', function () {
    $this->actingAs($this->user);

    FTP::fake();

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => FTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 21,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
            'ssl' => true,
            'passive' => true,
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'ssl' => false,
    ])
        ->assertSessionDoesntHaveErrors();

    $storageProvider->refresh();

    expect($storageProvider->credentials['ssl'])->toBeFalse()
        ->and($storageProvider->credentials['passive'])->toBeTrue();
});

test('update surfaces a connection exception as a validation error', function () {
    $this->actingAs($this->user);

    SFTP::swap(new class extends SFTPFake
    {
        public function connect(string $host, int $port, string $username, string $password): bool
        {
            throw new RuntimeException('/var/www/secret/path exploded');
        }
    });

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => SFTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
        ],
    ]);

    $response = $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'password' => 'new-password',
    ]);

    $response->assertSessionHasErrors([
        'provider' => "Couldn't connect to the provider",
    ]);

    $storageProvider->refresh();

    expect($storageProvider->credentials['password'])->toBe('original-password');
});

test('providers list survives a provider with no registered handler', function () {
    $this->actingAs($this->user);

    StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => 'removed-plugin-provider',
        'credentials' => ['key' => 'value'],
    ]);

    $this->get(route('storage-providers'))
        ->assertOk();
});

test('update rejects a provider with no registered handler', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => 'removed-plugin-provider',
        'profile' => 'original',
        'credentials' => ['key' => 'value'],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
    ])
        ->assertSessionHasErrors('provider');

    $storageProvider->refresh();

    expect($storageProvider->profile)->toBe('original');
});

test('update rejects a non boolean value for a checkbox credential', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => FTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 21,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
            'ssl' => true,
            'passive' => true,
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'ssl' => 'false',
    ])
        ->assertSessionHasErrors('ssl');

    $storageProvider->refresh();

    expect($storageProvider->credentials['ssl'])->toBeTrue();
});

test('ftp connection is closed when the login fails', function () {
    $closed = 0;

    FTP::swap(new class($closed) extends FTPFake
    {
        public function __construct(private int &$closed) {}

        public function login(string $username, string $password, bool|Connection $connection): bool
        {
            return false;
        }

        public function close(bool|Connection $connection): void
        {
            $this->closed++;
        }
    });

    $provider = (new StorageProviderModel([
        'provider' => FTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 21,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'wrong-password',
            'ssl' => false,
            'passive' => true,
        ],
    ]))->provider();

    expect($provider->connect([
        'host' => '1.2.3.4',
        'port' => 21,
        'username' => 'username',
        'password' => 'wrong-password',
        'ssl' => false,
    ]))->toBeFalse()
        ->and($closed)->toBe(1);
});

test('update changes dropbox credentials and reconnects', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*oauth2/token' => Http::response(['access_token' => 'fresh-access', 'expires_in' => 14400]),
        '*' => Http::response([], 200),
    ]);

    $storageProvider = StorageProviderModel::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'app_key' => 'new-app-key',
        'app_secret' => 'new-app-secret',
        'refresh_token' => '',
    ])
        ->assertSessionDoesntHaveErrors();

    $storageProvider->refresh();

    expect($storageProvider->credentials['app_key'])->toBe('new-app-key')
        ->and($storageProvider->credentials['app_secret'])->toBe('new-app-secret')
        ->and($storageProvider->credentials['refresh_token'])->toBe('test-refresh-token');
});

test('update rejects dropbox credentials that fail to connect', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*oauth2/token' => Http::response([], 401),
    ]);

    $storageProvider = StorageProviderModel::factory()->dropbox()->create([
        'user_id' => $this->user->id,
        'profile' => 'original',
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'app_secret' => 'bad-app-secret',
    ])
        ->assertSessionHasErrors('provider');

    $storageProvider->refresh();

    expect($storageProvider->credentials['app_secret'])->toBe('test-app-secret')
        ->and($storageProvider->profile)->toBe('original');
});

test('dropbox editable data excludes its secrets', function () {
    $storageProvider = StorageProviderModel::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $editableData = (array) $storageProvider->editableDataFor($this->user);

    expect($editableData)->toBe(['app_key' => 'test-app-key']);
});

test('editing a provider forgets its cached state', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    Cache::put("dropbox_token_{$storageProvider->id}", 'stale-token', 3600);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
    ])
        ->assertSessionDoesntHaveErrors();

    expect(Cache::has("dropbox_token_{$storageProvider->id}"))->toBeFalse();
});

test('providers list exposes editable data to the dialog', function () {
    $this->actingAs($this->user);

    StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
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

    $response = $this->get(route('storage-providers'))
        ->assertOk();

    $response->assertDontSee('super-secret');

    $rows = $response->viewData('page')['props']['storageProviders']['data'];

    $editableData = (array) $rows[0]['editable_data'];

    expect($editableData['bucket'])->toBe('test-bucket')
        ->and($editableData)->not->toHaveKey('secret');
});

test('update validates provider fields', function () {
    $this->actingAs($this->user);

    $storageProvider = StorageProviderModel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => SFTPProvider::id(),
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'original-password',
        ],
    ]);

    $this->patch(route('storage-providers.update', $storageProvider), [
        'name' => 'updated',
        'port' => 99999,
    ])
        ->assertSessionHasErrors('port');
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
                'provider' => FTPProvider::id(),
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
                'provider' => FTPProvider::id(),
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
