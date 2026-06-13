<?php

namespace Tests\Feature;

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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServerProvidersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_connect_provider(string $provider, array $input): void
    {
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
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_cannot_connect_to_provider(string $provider, array $input): void
    {
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
    }

    public function test_see_providers_list(): void
    {
        $this->actingAs($this->user);

        ServerProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('server-providers'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('server-providers/index'));
    }

    #[DataProvider('data')]
    public function test_delete_provider(string $provider, array $input): void
    {
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
    }

    #[DataProvider('data')]
    public function test_cannot_delete_provider(string $provider, array $input): void
    {
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
    }

    public function test_user_cannot_access_other_users_server_provider(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $serverProvider = ServerProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->get(route('server-providers.regions', $serverProvider))
            ->assertForbidden();
    }

    public function test_user_cannot_update_other_users_server_provider(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $serverProvider = ServerProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->patch(route('server-providers.update', $serverProvider), [
            'name' => 'hacked',
        ])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_server_provider(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $serverProvider = ServerProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->delete(route('server-providers.destroy', $serverProvider))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_server_providers(): void
    {
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
    }

    public function test_cannot_manipulate_user_id_on_creation(): void
    {
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
    }

    public function test_cannot_transfer_ownership_via_update(): void
    {
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

        $this->assertEquals($this->user->id, $serverProvider->user_id);
        $this->assertNotEquals($otherUser->id, $serverProvider->user_id);
    }

    public function test_user_can_only_see_own_server_providers_in_list(): void
    {
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
    }

    public function test_creating_server_provider_dispatches_socket_event(): void
    {
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
    }

    public function test_deleting_server_provider_dispatches_socket_event(): void
    {
        Event::fake([SocketEvent::class]);
        $this->actingAs($this->user);

        $serverProvider = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $this->delete(route('server-providers.destroy', $serverProvider))->assertSessionDoesntHaveErrors();

        Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => $event->data->type === 'server-provider.deleted'
            && $event->data->data['id'] === $serverProvider->id);
    }

    public function test_hetzner_plans_expose_availability_and_order_available_first(): void
    {
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

        $this->assertSame(['cpx22', 'ccx13', 'cax11'], array_keys($plans));
        $this->assertTrue($plans['ccx13']['available']);
        $this->assertStringContainsString('(18.49/mo)', $plans['ccx13']['label']);
        $this->assertFalse($plans['cax11']['available']);
        $this->assertStringNotContainsString('/mo', $plans['cax11']['label']);
        $this->assertArrayNotHasKey('cpx12', $plans);
    }

    public function test_digital_ocean_plans_show_every_size_and_grey_unavailable(): void
    {
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

        $this->assertSame(['s-1vcpu-1gb', 's-2vcpu-4gb', 's-1vcpu-512mb-10gb'], array_keys($plans));
        $this->assertTrue($plans['s-1vcpu-1gb']['available']);
        $this->assertStringContainsString('(6.00/mo)', $plans['s-1vcpu-1gb']['label']);
        $this->assertFalse($plans['s-1vcpu-512mb-10gb']['available']);
        $this->assertStringNotContainsString('/mo', $plans['s-1vcpu-512mb-10gb']['label']);
    }

    public function test_vultr_plans_show_every_plan_and_grey_unavailable(): void
    {
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

        $this->assertSame(['vc2-1c-1gb', 'vc2-2c-4gb', 'vc2-1c-0.5gb'], array_keys($plans));
        $this->assertTrue($plans['vc2-1c-1gb']['available']);
        $this->assertStringContainsString('(5.00/mo)', $plans['vc2-1c-1gb']['label']);
        $this->assertFalse($plans['vc2-1c-0.5gb']['available']);
        $this->assertStringNotContainsString('/mo', $plans['vc2-1c-0.5gb']['label']);
    }

    public function test_linode_plans_grey_classes_unsupported_by_region(): void
    {
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

        $this->assertSame(['g6-standard-1', 'g1-gpu-rtx6000-1', 'g7-premium-2'], array_keys($plans));
        $this->assertTrue($plans['g6-standard-1']['available']);
        $this->assertStringContainsString('50 Disk', $plans['g6-standard-1']['label']);
        $this->assertStringContainsString('(12.00/mo)', $plans['g6-standard-1']['label']);
        $this->assertFalse($plans['g1-gpu-rtx6000-1']['available']);
        $this->assertFalse($plans['g7-premium-2']['available']);
        $this->assertStringNotContainsString('/mo', $plans['g7-premium-2']['label']);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function data(): array
    {
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
    }
}
