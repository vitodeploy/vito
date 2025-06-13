<?php

namespace Tests\Feature\API;

use App\Models\ServerProvider;
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
