<?php

namespace Tests\Feature\API;

use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SourceControlsTest extends TestCase
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

        $input = array_merge([
            'name' => 'test',
            'provider' => $provider,
        ], $input);

        $this->json('POST', route('api.projects.source-controls.create', [
            'project' => $this->user->current_project_id,
        ]), $input)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $provider,
                'name' => 'test',
            ]);
    }

    /**
     * @dataProvider data
     */
    #[DataProvider('data')]
    public function test_delete_provider(string $provider): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
            'user_id' => $this->user->id,
        ]);

        $this->json('DELETE', route('api.projects.source-controls.delete', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    /**
     * @dataProvider data
     */
    #[DataProvider('data')]
    public function test_cannot_delete_provider(string $provider): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
            'user_id' => $this->user->id,
        ]);

        $this->site->update([
            'source_control_id' => $sourceControl->id,
        ]);

        $this->json('DELETE', route('api.projects.source-controls.delete', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This source control is being used by a site.',
            ]);

        $this->assertNotSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @dataProvider data
     *
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_edit_source_control(string $provider, array $input): void
    {
        Http::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'old-name',
            'url' => $input['url'] ?? null,
            'user_id' => $this->user->id,
        ]);

        $this->json('PUT', route('api.projects.source-controls.update', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]), array_merge([
            'name' => 'new-name',
        ], $input))
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $provider,
                'name' => 'new-name',
            ]);

        $sourceControl->refresh();

        $this->assertEquals('new-name', $sourceControl->profile);
        if (isset($input['url'])) {
            $this->assertEquals($input['url'], $sourceControl->url);
        }
    }

    public function test_api_user_cannot_access_other_users_source_control(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('GET', route('api.projects.source-controls.show', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertForbidden();
    }

    public function test_api_user_cannot_update_other_users_source_control(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Http::fake();

        $this->json('PUT', route('api.projects.source-controls.update', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]), [
            'name' => 'hacked',
            'token' => 'hacked-token',
        ])
            ->assertForbidden();
    }

    public function test_api_user_cannot_delete_other_users_source_control(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('DELETE', route('api.projects.source-controls.delete', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertForbidden();
    }

    public function test_api_guest_cannot_access_source_controls(): void
    {
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->json('GET', route('api.projects.source-controls', [
            'project' => $this->user->current_project_id,
        ]))
            ->assertUnauthorized();

        $this->json('POST', route('api.projects.source-controls.create', [
            'project' => $this->user->current_project_id,
        ]), [])
            ->assertUnauthorized();

        $this->json('DELETE', route('api.projects.source-controls.delete', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertUnauthorized();
    }

    public function test_api_insufficient_scopes_denies_access(): void
    {
        Sanctum::actingAs($this->user, ['read']); // Only read scope

        $data = [
            'provider' => Github::id(),
            'name' => 'test',
            'token' => 'fake-token',
        ];

        $this->json('POST', route('api.projects.source-controls.create', [
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
            'provider' => Github::id(),
            'name' => 'test',
            'token' => 'fake-token',
            'user_id' => $otherUser->id,
        ];

        $this->json('POST', route('api.projects.source-controls.create', [
            'project' => $this->user->current_project_id,
        ]), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => Github::id(),
                'name' => 'test',
            ]);

        $this->assertDatabaseHas('source_controls', [
            'profile' => 'test',
            'provider' => Github::id(),
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('source_controls', [
            'profile' => 'test',
            'provider' => Github::id(),
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_api_user_can_only_see_own_source_controls_in_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();

        $ownSourceControl = SourceControl::factory()->create([
            'user_id' => $this->user->id,
            'profile' => 'own-source-control',
        ]);

        $otherSourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
            'profile' => 'other-source-control',
        ]);

        $this->json('GET', route('api.projects.source-controls', [
            'project' => $this->user->current_project_id,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $ownSourceControl->id,
                'provider' => $ownSourceControl->provider,
            ])
            ->assertJsonMissing([
                'id' => $otherSourceControl->id,
            ]);
    }

    public function test_api_soft_deleted_source_control_cannot_be_accessed(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $sourceControl->delete();

        $this->json('GET', route('api.projects.source-controls.show', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertNotFound();
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function data(): array
    {
        return [
            [Github::id(), ['token' => 'test']],
            [Github::id(), ['token' => 'test', 'global' => '1']],
            [Gitlab::id(), ['token' => 'test']],
            [Gitlab::id(), ['token' => 'test', 'url' => 'https://git.example.com/']],
            [Bitbucket::id(), ['username' => 'test', 'password' => 'test']],
        ];
    }
}
