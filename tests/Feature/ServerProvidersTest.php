<?php

use App\Events\SocketEvent;
use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('connect provider', function (string $provider, array $input) {
    $this->actingAs($this->user);

    Http::fake();

    $data = array_merge(
        [
            'provider' => $provider,
            'name' => 'profile',
        ],
        $input
    );
    $this->post(route('server-providers.store'), $data)
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('server_providers', [
        'provider' => $provider,
        'profile' => 'profile',
        'project_id' => isset($input['global']) ? null : $this->user->current_project_id,
    ]);
})->with('data');

test('cannot connect to provider', function (string $provider, array $input) {
    $this->actingAs($this->user);

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
    $this->post(route('server-providers.store'), $data)
        ->assertSessionHasErrors('provider');

    $this->assertDatabaseMissing('server_providers', [
        'provider' => $provider,
        'profile' => 'profile',
    ]);
})->with('data');

test('see providers list', function () {
    $this->actingAs($this->user);

    ServerProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->get(route('server-providers'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-providers/index'));
});

test('delete provider', function (string $provider, array $input) {
    unset($input);

    $this->actingAs($this->user);

    $provider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => $provider,
    ]);

    $this->delete(route('server-providers.destroy', $provider))
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('server-providers'));

    $this->assertDatabaseMissing('server_providers', [
        'id' => $provider->id,
    ]);
})->with('data');

test('cannot delete provider', function (string $provider, array $input) {
    unset($input);

    $this->actingAs($this->user);

    $provider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => $provider,
    ]);

    $this->server->update([
        'provider_id' => $provider->id,
    ]);

    $this->delete(route('server-providers.destroy', $provider))
        ->assertSessionHasErrors([
            'provider' => 'This server provider is being used by a server.',
        ]);

    $this->assertDatabaseHas('server_providers', [
        'id' => $provider->id,
    ]);
})->with('data');

test('user cannot access other users server provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->get(route('server-providers.regions', $serverProvider))
        ->assertForbidden();
});

test('user cannot update other users server provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->patch(route('server-providers.update', $serverProvider), [
        'name' => 'hacked',
    ])
        ->assertForbidden();
});

test('user cannot delete other users server provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->delete(route('server-providers.destroy', $serverProvider))
        ->assertForbidden();
});

test('guest cannot access server providers', function () {
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->get(route('server-providers'))
        ->assertRedirect('/');

    $this->get(route('server-providers.regions', $serverProvider))
        ->assertRedirect('/');

    $this->post(route('server-providers.store'), [])
        ->assertRedirect('/');

    $this->patch(route('server-providers.update', $serverProvider), [])
        ->assertRedirect('/');

    $this->delete(route('server-providers.destroy', $serverProvider))
        ->assertRedirect('/');
});

test('cannot manipulate user id on creation', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();

    Http::fake();

    $data = [
        'provider' => DigitalOcean::id(),
        'name' => 'test',
        'token' => 'fake-token',
        'user_id' => $otherUser->id,
    ];

    $this->post(route('server-providers.store'), $data)
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('server_providers', [
        'profile' => 'test',
        'provider' => DigitalOcean::id(),
        'user_id' => $this->user->id,
    ]);

    $this->assertDatabaseMissing('server_providers', [
        'profile' => 'test',
        'provider' => DigitalOcean::id(),
        'user_id' => $otherUser->id,
    ]);
});

test('cannot transfer ownership via update', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'original',
    ]);

    $this->patch(route('server-providers.update', $serverProvider), [
        'name' => 'updated',
        'user_id' => $otherUser->id,
    ]);

    $serverProvider->refresh();

    expect($serverProvider->user_id)->toEqual($this->user->id);
    $this->assertNotEquals($otherUser->id, $serverProvider->user_id);
});

test('user can only see own server providers in list', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();

    $ownProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'own-provider',
    ]);

    $otherProvider = ServerProvider::factory()->create([
        'user_id' => $otherUser->id,
        'profile' => 'other-provider',
    ]);

    $response = $this->get(route('server-providers'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-providers/index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page->has('serverProviders.data')
        ->where('serverProviders.data.0.id', $ownProvider->id)
        ->whereNot('serverProviders.data.0.id', $otherProvider->id)
    );
});

test('creating server provider dispatches socket event', function () {
    Event::fake([SocketEvent::class]);
    $this->actingAs($this->user);
    Http::fake();

    $this->post(route('server-providers.store'), [
        'provider' => Hetzner::id(),
        'name' => 'hetty',
        'token' => 'token',
    ])->assertSessionDoesntHaveErrors();

    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => $event->data->type === 'server-provider.created'
        && $event->data->data['name'] === 'hetty');
});

test('deleting server provider dispatches socket event', function () {
    Event::fake([SocketEvent::class]);
    $this->actingAs($this->user);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->delete(route('server-providers.destroy', $serverProvider))->assertSessionDoesntHaveErrors();

    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => $event->data->type === 'server-provider.deleted'
        && $event->data->data['id'] === $serverProvider->id);
});

test('hetzner plans expose availability and order available first', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*' => Http::response([
            'server_types' => [
                [
                    'name' => 'cpx22', 'cores' => 2, 'memory' => 4, 'disk' => 80,
                    'prices' => [['location' => 'fsn1', 'price_monthly' => ['net' => '9.4900000000']]],
                    'locations' => [
                        ['name' => 'fsn1', 'available' => true, 'deprecation' => null],
                    ],
                ],
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
                [
                    'name' => 'cpx12', 'cores' => 1, 'memory' => 2, 'disk' => 40,
                    'prices' => [['location' => 'sin', 'price_monthly' => ['net' => '9.4900000000']]],
                    'locations' => [
                        ['name' => 'sin', 'available' => true, 'deprecation' => null],
                    ],
                ],
            ],
        ], 200),
    ]);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => Hetzner::id(),
        'credentials' => ['token' => 'token'],
    ]);

    $plans = $this->get(route('server-providers.plans', [
        'serverProvider' => $serverProvider->id,
        'region' => 'fsn1',
    ]))
        ->assertSuccessful()
        ->json();

    expect(array_keys($plans))->toBe(['cpx22', 'ccx13', 'cax11']);
    expect($plans['ccx13']['available'])->toBeTrue();
    $this->assertStringContainsString('(18.49/mo)', $plans['ccx13']['label']);
    expect($plans['cax11']['available'])->toBeFalse();
    $this->assertStringNotContainsString('/mo', $plans['cax11']['label']);
    $this->assertArrayNotHasKey('cpx12', $plans);
});

test('digital ocean plans show every size and grey unavailable', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*' => Http::response([
            'sizes' => [
                [
                    'slug' => 's-1vcpu-1gb', 'description' => 'Basic', 'vcpus' => 1, 'memory' => 1024, 'disk' => 25,
                    'price_monthly' => 6, 'available' => true, 'regions' => ['lon1', 'nyc1'],
                ],
                [
                    'slug' => 's-2vcpu-4gb', 'description' => 'Basic', 'vcpus' => 2, 'memory' => 4096, 'disk' => 80,
                    'price_monthly' => 24, 'available' => true, 'regions' => ['lon1'],
                ],
                [
                    'slug' => 's-1vcpu-512mb-10gb', 'description' => 'Basic', 'vcpus' => 1, 'memory' => 512, 'disk' => 10,
                    'price_monthly' => 4, 'available' => true, 'regions' => ['nyc1'],
                ],
            ],
        ], 200),
    ]);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => DigitalOcean::id(),
        'credentials' => ['token' => 'token'],
    ]);

    $plans = $this->get(route('server-providers.plans', [
        'serverProvider' => $serverProvider->id,
        'region' => 'lon1',
    ]))
        ->assertSuccessful()
        ->json();

    expect(array_keys($plans))->toBe(['s-1vcpu-1gb', 's-2vcpu-4gb', 's-1vcpu-512mb-10gb']);
    expect($plans['s-1vcpu-1gb']['available'])->toBeTrue();
    $this->assertStringContainsString('(6.00/mo)', $plans['s-1vcpu-1gb']['label']);
    expect($plans['s-1vcpu-512mb-10gb']['available'])->toBeFalse();
    $this->assertStringNotContainsString('/mo', $plans['s-1vcpu-512mb-10gb']['label']);
});

test('vultr plans show every plan and grey unavailable', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*' => Http::response([
            'plans' => [
                [
                    'id' => 'vc2-1c-1gb', 'type' => 'vc2', 'vcpu_count' => 1, 'ram' => 1024, 'disk' => 25,
                    'monthly_cost' => 5, 'locations' => ['ams', 'ewr'],
                ],
                [
                    'id' => 'vc2-2c-4gb', 'type' => 'vc2', 'vcpu_count' => 2, 'ram' => 4096, 'disk' => 80,
                    'monthly_cost' => 20, 'locations' => ['ams'],
                ],
                [
                    'id' => 'vc2-1c-0.5gb', 'type' => 'vc2', 'vcpu_count' => 1, 'ram' => 512, 'disk' => 10,
                    'monthly_cost' => 2.5, 'locations' => ['ewr'],
                ],
            ],
        ], 200),
    ]);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => Vultr::id(),
        'credentials' => ['token' => 'token'],
    ]);

    $plans = $this->get(route('server-providers.plans', [
        'serverProvider' => $serverProvider->id,
        'region' => 'ams',
    ]))
        ->assertSuccessful()
        ->json();

    expect(array_keys($plans))->toBe(['vc2-1c-1gb', 'vc2-2c-4gb', 'vc2-1c-0.5gb']);
    expect($plans['vc2-1c-1gb']['available'])->toBeTrue();
    $this->assertStringContainsString('(5.00/mo)', $plans['vc2-1c-1gb']['label']);
    expect($plans['vc2-1c-0.5gb']['available'])->toBeFalse();
    $this->assertStringNotContainsString('/mo', $plans['vc2-1c-0.5gb']['label']);
});

test('linode plans grey classes unsupported by region', function () {
    $this->actingAs($this->user);

    Http::fake([
        'api.linode.com/v4/linode/types*' => Http::response([
            'data' => [
                [
                    'id' => 'g6-standard-1', 'label' => 'Linode 2GB', 'class' => 'standard',
                    'vcpus' => 1, 'memory' => 2048, 'disk' => 51200,
                    'price' => ['monthly' => 12.0, 'hourly' => 0.018], 'region_prices' => [],
                ],
                [
                    'id' => 'g1-gpu-rtx6000-1', 'label' => 'RTX6000 GPU', 'class' => 'gpu',
                    'vcpus' => 8, 'memory' => 32768, 'disk' => 655360,
                    'price' => ['monthly' => 1000.0, 'hourly' => 1.5], 'region_prices' => [],
                ],
                [
                    'id' => 'g7-premium-2', 'label' => 'Premium 4GB', 'class' => 'premium',
                    'vcpus' => 2, 'memory' => 4096, 'disk' => 81920,
                    'price' => ['monthly' => 36.0, 'hourly' => 0.054],
                    'region_prices' => [['id' => 'eu-test', 'monthly' => 40.0, 'hourly' => 0.06]],
                ],
            ],
        ], 200),
        'api.linode.com/v4/regions*' => Http::response([
            'data' => [
                ['id' => 'eu-test', 'label' => 'Test', 'capabilities' => ['Linodes']],
            ],
        ], 200),
    ]);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => Linode::id(),
        'credentials' => ['token' => 'token'],
    ]);

    $plans = $this->get(route('server-providers.plans', [
        'serverProvider' => $serverProvider->id,
        'region' => 'eu-test',
    ]))
        ->assertSuccessful()
        ->json();

    expect(array_keys($plans))->toBe(['g6-standard-1', 'g1-gpu-rtx6000-1', 'g7-premium-2']);
    expect($plans['g6-standard-1']['available'])->toBeTrue();
    $this->assertStringContainsString('50 Disk', $plans['g6-standard-1']['label']);
    $this->assertStringContainsString('(12.00/mo)', $plans['g6-standard-1']['label']);
    expect($plans['g1-gpu-rtx6000-1']['available'])->toBeFalse();
    expect($plans['g7-premium-2']['available'])->toBeFalse();
    $this->assertStringNotContainsString('/mo', $plans['g7-premium-2']['label']);
});

dataset('data', /** @return array<int, array{0: string, 1: array<string, mixed>}> */ function (): array {
    return [
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
