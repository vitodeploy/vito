<?php

namespace Tests\Feature\API;

use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
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
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_cannot_connect_to_provider(string $provider, array $input): void
    {
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
    }

    public function test_see_providers_list(): void
    {
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
    }

    #[DataProvider('data')]
    public function test_delete_provider(string $provider): void
    {
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
    }

    #[DataProvider('data')]
    public function test_cannot_delete_provider(string $provider): void
    {
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
    }

    public function test_api_user_cannot_access_other_users_server_provider(): void
    {
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
    }

    public function test_api_user_cannot_update_other_users_server_provider(): void
    {
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
    }

    public function test_api_user_cannot_delete_other_users_server_provider(): void
    {
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
    }

    public function test_api_guest_cannot_access_server_providers(): void
    {
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
    }

    public function test_api_insufficient_scopes_denies_access(): void
    {
        Sanctum::actingAs($this->user, ['read']); // Only read scope

        $data = [
            'provider' => DigitalOcean::id(),
            'name' => 'test',
            'token' => 'fake-token',
        ];

        $this->json('POST', route('api.projects.server-providers.create', [
            'project' => $this->user->current_project_id,
        ]), $data)
            ->assertForbidden();
    }

    public function test_api_cannot_manipulate_user_id_on_creation(): void
    {
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
    }

    public function test_api_user_can_only_see_own_server_providers_in_list(): void
    {
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
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_get_regions(string $provider, array $input): void
    {
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
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_get_plans(string $provider, array $input): void
    {
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
    }

    public function test_cannot_access_regions_without_authentication(): void
    {
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
    }

    public function test_cannot_access_plans_without_authentication(): void
    {
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
    }

    public function test_cannot_access_other_users_server_provider_regions(): void
    {
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
    }

    public function test_cannot_access_other_users_server_provider_plans(): void
    {
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
    }

    /**
     * @return array<array<int, mixed>>
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
