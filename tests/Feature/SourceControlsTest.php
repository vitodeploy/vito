<?php

namespace Tests\Feature;

use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\BitbucketV2;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SourceControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_connect_provider(string $provider, ?string $customUrl, array $input): void
    {
        $this->actingAs($this->user);

        // Configure HTTP fake responses for BitbucketV2 OAuth flow
        if ($provider === BitbucketV2::id()) {
            Http::fake([
                'bitbucket.org/site/oauth2/access_token' => Http::response([
                    'access_token' => 'fake-access-token',
                    'token_type' => 'Bearer',
                ], 200),
                'api.bitbucket.org/2.0/user' => Http::response([
                    'username' => 'test-user',
                ], 200),
            ]);
        } else {
            Http::fake();
        }

        $input = array_merge([
            'name' => 'test',
            'provider' => $provider,
        ], $input);

        if ($customUrl !== null) {
            $input['url'] = $customUrl;
        }

        $this->post(route('source-controls.store'), $input);

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
            'url' => $customUrl,
        ]);

        if (isset($input['global']) && $input['global']) {
            $this->assertDatabaseHas('source_controls', [
                'provider' => $provider,
                'url' => $customUrl,
                'project_id' => null,
            ]);
        } else {
            $this->assertDatabaseHas('source_controls', [
                'provider' => $provider,
                'url' => $customUrl,
                'project_id' => $this->user->current_project_id,
            ]);
        }
    }

    #[DataProvider('data')]
    public function test_delete_provider(string $provider): void
    {
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('source-controls.destroy', $sourceControl))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('source-controls'));

        $this->assertSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    #[DataProvider('data')]
    public function test_cannot_delete_provider(string $provider): void
    {
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
            'user_id' => $this->user->id,
        ]);

        $this->site->update([
            'source_control_id' => $sourceControl->id,
        ]);

        $this->delete(route('source-controls.destroy', $sourceControl))
            ->assertSessionHasErrors([
                'source_control' => 'This source control is being used by a site.',
            ]);

        $this->assertNotSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_edit_source_control(string $provider, ?string $url, array $input): void
    {
        Http::fake();

        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'old-name',
            'url' => $url,
            'user_id' => $this->user->id,
        ]);

        $input['name'] = 'new-name';

        $this->patch(route('source-controls.update', $sourceControl), $input)
            ->assertSessionDoesntHaveErrors();

        $sourceControl->refresh();

        $this->assertEquals('new-name', $sourceControl->profile);
        $this->assertEquals($url, $sourceControl->url);
    }

    public function test_user_cannot_update_other_users_source_control(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Http::fake();

        $this->patch(route('source-controls.update', $sourceControl), [
            'name' => 'hacked',
            'token' => 'hacked-token',
        ])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_source_control(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->delete(route('source-controls.destroy', $sourceControl))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_source_controls(): void
    {
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('source-controls'))
            ->assertRedirect('/');

        $this->post(route('source-controls.store'), [])
            ->assertRedirect('/');

        $this->patch(route('source-controls.update', $sourceControl), [])
            ->assertRedirect('/');

        $this->delete(route('source-controls.destroy', $sourceControl))
            ->assertRedirect('/');
    }

    public function test_cannot_manipulate_user_id_on_creation(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();

        Http::fake();

        $data = [
            'provider' => Github::id(),
            'name' => 'test',
            'token' => 'fake-token',
            'user_id' => $otherUser->id,
        ];

        $this->post(route('source-controls.store'), $data);

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

    public function test_cannot_transfer_ownership_via_update(): void
    {
        Http::fake();

        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $sourceControl = SourceControl::factory()->create([
            'user_id' => $this->user->id,
            'profile' => 'original',
        ]);

        $this->patch(route('source-controls.update', $sourceControl), [
            'name' => 'updated',
            'token' => 'new-token',
            'user_id' => $otherUser->id,
        ]);

        $sourceControl->refresh();

        $this->assertEquals($this->user->id, $sourceControl->user_id);
        $this->assertNotEquals($otherUser->id, $sourceControl->user_id);
    }

    public function test_user_can_only_see_own_source_controls_in_list(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();

        $ownSourceControl = SourceControl::factory()->create([
            'user_id' => $this->user->id,
            'profile' => 'own-source-control',
        ]);

        $otherSourceControl = SourceControl::factory()->create([
            'user_id' => $otherUser->id,
            'profile' => 'other-source-control',
        ]);

        $response = $this->get(route('source-controls'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('source-controls/index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->has('sourceControls.data')
            ->where('sourceControls.data.0.id', $ownSourceControl->id)
            ->whereNot('sourceControls.data.0.id', $otherSourceControl->id)
        );
    }

    /**
     * @return array<int, mixed>
     */
    public static function data(): array
    {
        return [
            [Github::id(), null, ['token' => 'test']],
            [Github::id(), null, ['token' => 'test', 'global' => true]],
            [Gitlab::id(), null, ['token' => 'test']],
            [Gitlab::id(), 'https://git.example.com/', ['token' => 'test']],
            [Bitbucket::id(), null, ['username' => 'test', 'password' => 'test']],
            [BitbucketV2::id(), null, ['key' => 'test', 'secret' => 'test']],
        ];
    }
}
