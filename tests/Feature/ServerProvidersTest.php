<?php

namespace Tests\Feature;

use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function test_delete_provider(string $provider): void
    {
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
    public function test_cannot_delete_provider(string $provider): void
    {
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
