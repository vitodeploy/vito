<?php

use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('connect provider', function (string $provider, array $input) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    Http::fake();

    $data = array_merge(
        [
            'provider' => $provider,
            'name' => 'profile',
        ],
        $input
    );
    $this->json('POST', route('api.projects.server-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => $provider,
            'name' => 'profile',
            'project_id' => isset($input['global']) ? null : $this->user->current_project_id,
        ]);
})->with('data');

test('cannot connect to provider', function (string $provider, array $input) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $data = array_merge(
        [
            'provider' => $provider,
            'name' => 'profile',
        ],
        $input
    );
    $this->json('POST', route('api.projects.server-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertJsonValidationErrorFor('provider');
})->with('data');

test('see providers list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var ServerProvider $provider */
    $provider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->json('GET', route('api.projects.server-providers', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $provider->id,
            'provider' => $provider->provider,
        ]);
});

test('delete provider', function (string $provider, array $input) {
    unset($input);

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var ServerProvider $provider */
    $provider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => $provider,
    ]);

    $this->json('DELETE', route('api.projects.server-providers.delete', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $provider->id,
    ]))
        ->assertNoContent();
})->with('data');

test('cannot delete provider', function (string $provider, array $input) {
    unset($input);

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var ServerProvider $provider */
    $provider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => $provider,
    ]);

    $this->server->update([
        'provider_id' => $provider->id,
    ]);

    $this->json('DELETE', route('api.projects.server-providers.delete', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $provider->id,
    ]))
        ->assertJsonValidationErrors([
            'provider' => 'This server provider is being used by a server.',
        ]);
})->with('data');

test('api user cannot access other users server provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('GET', route('api.projects.server-providers.show', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]))
        ->assertForbidden();
});

test('api user cannot update other users server provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('PUT', route('api.projects.server-providers.update', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]), [
        'name' => 'hacked',
    ])
        ->assertForbidden();
});

test('api user cannot delete other users server provider', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('DELETE', route('api.projects.server-providers.delete', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]))
        ->assertForbidden();
});

test('api guest cannot access server providers', function () {
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->json('GET', route('api.projects.server-providers', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertUnauthorized();

    $this->json('POST', route('api.projects.server-providers.create', [
        'project' => $this->user->current_project_id,
    ]), [])
        ->assertUnauthorized();

    $this->json('DELETE', route('api.projects.server-providers.delete', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]))
        ->assertUnauthorized();
});

test('api insufficient scopes denies access', function () {
    Sanctum::actingAs($this->user, ['read']);

    // Only read scope
    $data = [
        'provider' => DigitalOcean::id(),
        'name' => 'test',
        'token' => 'fake-token',
    ];

    $this->json('POST', route('api.projects.server-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertForbidden();
});

test('api cannot manipulate user id on creation', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();

    Http::fake();

    $data = [
        'provider' => DigitalOcean::id(),
        'name' => 'test',
        'token' => 'fake-token',
        'user_id' => $otherUser->id,
    ];

    $this->json('POST', route('api.projects.server-providers.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => DigitalOcean::id(),
            'name' => 'test',
            'user_id' => $this->user->id,
        ])
        ->assertJsonMissing([
            'user_id' => $otherUser->id,
        ]);
});

test('api user can only see own server providers in list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();

    $ownProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'own-provider',
    ]);

    $otherProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
        'profile' => 'other-provider',
    ]);

    $response = $this->json('GET', route('api.projects.server-providers', [
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

test('get regions', function (string $provider, array $input) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    // Mock the provider's regions method
    Http::fake([
        '*' => Http::response([
            ['id' => 'nyc1', 'name' => 'New York 1', 'country' => 'US', 'available' => true],
            ['id' => 'sfo1', 'name' => 'San Francisco 1', 'country' => 'US', 'available' => true],
        ], 200),
    ]);

    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => $provider,
        'credentials' => $input,
    ]);

    $this->json('GET', route('api.projects.server-providers.regions', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]))
        ->assertSuccessful()
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'country',
                'available',
            ],
        ]);
})->with('data');

test('get plans', function (string $provider, array $input) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    // Mock the provider's plans method
    Http::fake([
        '*' => Http::response([
            [
                'id' => 's-1vcpu-1gb',
                'name' => 'Basic',
                'memory' => 1024,
                'vcpus' => 1,
                'disk' => 25,
                'price_monthly' => 5.0,
                'price_hourly' => 0.007,
                'available' => true,
            ],
            [
                'id' => 's-1vcpu-2gb',
                'name' => 'Standard',
                'memory' => 2048,
                'vcpus' => 1,
                'disk' => 50,
                'price_monthly' => 10.0,
                'price_hourly' => 0.014,
                'available' => true,
            ],
        ], 200),
    ]);

    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => $provider,
        'credentials' => $input,
    ]);

    $this->json('GET', route('api.projects.server-providers.plans', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
        'region' => 'nyc1',
    ]))
        ->assertSuccessful()
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'memory',
                'vcpus',
                'disk',
                'price_monthly',
                'price_hourly',
                'available',
            ],
        ]);
})->with('data');

test('hetzner plans endpoint returns flat available only', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    Http::fake([
        '*' => Http::response([
            'server_types' => [
                [
                    'name' => 'ccx13', 'cores' => 2, 'memory' => 8, 'disk' => 80,
                    'prices' => [['location' => 'fsn1', 'price_monthly' => ['net' => '18.4900000000']]],
                    'locations' => [
                        ['name' => 'fsn1', 'available' => true, 'deprecation' => null],
                    ],
                ],
                [
                    'name' => 'cax11', 'cores' => 2, 'memory' => 4, 'disk' => 40,
                    'prices' => [['location' => 'fsn1', 'price_monthly' => ['net' => '5.4900000000']]],
                    'locations' => [
                        ['name' => 'fsn1', 'available' => false, 'deprecation' => null],
                    ],
                ],
            ],
        ], 200),
    ]);

    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => Hetzner::id(),
        'credentials' => ['token' => 'token'],
    ]);

    $plans = $this->json('GET', route('api.projects.server-providers.plans', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
        'region' => 'fsn1',
    ]))
        ->assertSuccessful()
        ->json();

    expect($plans)->toHaveKey('ccx13');
    $this->assertArrayNotHasKey('cax11', $plans);
    expect($plans['ccx13'])->toBeString();
});

test('cannot access regions without authentication', function () {
    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->json('GET', route('api.projects.server-providers.regions', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]))
        ->assertUnauthorized();
});

test('cannot access plans without authentication', function () {
    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->json('GET', route('api.projects.server-providers.plans', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
        'region' => 'nyc1',
    ]))
        ->assertUnauthorized();
});

test('cannot access other users server provider regions', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var User $otherUser */
    $otherUser = User::factory()->create();

    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherUser->current_project_id,
    ]);

    $this->json('GET', route('api.projects.server-providers.regions', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
    ]))
        ->assertForbidden();
});

test('cannot access other users server provider plans', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var User $otherUser */
    $otherUser = User::factory()->create();

    /** @var ServerProvider $serverProvider */
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherUser->current_project_id,
    ]);

    $this->json('GET', route('api.projects.server-providers.plans', [
        'project' => $this->user->current_project_id,
        'serverProvider' => $serverProvider->id,
        'region' => 'nyc1',
    ]))
        ->assertForbidden();
});

/**
 * @return array<array<int, mixed>>
 */
dataset('data', function () {
    return [
        // [
        //     ServerProvider::AWS,
        //     [
        //         'key' => 'key',
        //         'secret' => 'secret',
        //     ],
        // ],
        [
            Linode::id(),
            [
                'token' => 'token',
            ],
        ],
        [
            Linode::id(),
            [
                'token' => 'token',
                'global' => 1,
            ],
        ],
        [
            DigitalOcean::id(),
            [
                'token' => 'token',
            ],
        ],
        [
            Vultr::id(),
            [
                'token' => 'token',
            ],
        ],
        [
            Hetzner::id(),
            [
                'token' => 'token',
            ],
        ],
    ];
});
