<?php

namespace Tests\Feature;

use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\BitbucketV2;
use App\SourceControlProviders\Gitea;
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
    public function test_delete_provider(string $provider, ?string $url, array $input): void
    {
        unset($url, $input);

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
    public function test_cannot_delete_provider(string $provider, ?string $url, array $input): void
    {
        unset($url, $input);

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

    public function test_connect_gitea_persists_ssh_port_in_provider_data(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        $this->post(route('source-controls.store'), [
            'name' => 'gitea-custom-port',
            'provider' => Gitea::id(),
            'token' => 'test-token',
            'url' => 'https://gitea.example.com/',
            'ssh_port' => 2222,
        ])->assertSessionDoesntHaveErrors();

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::query()
            ->where('provider', Gitea::id())
            ->where('profile', 'gitea-custom-port')
            ->firstOrFail();

        $this->assertSame(2222, $sourceControl->provider_data['ssh_port']);
        $this->assertSame('test-token', $sourceControl->provider_data['token']);
        $this->assertSame(2222, $sourceControl->provider()->getSshPort());
    }

    public function test_connect_gitea_without_ssh_port_defaults_to_22(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        $this->post(route('source-controls.store'), [
            'name' => 'gitea-default',
            'provider' => Gitea::id(),
            'token' => 'test-token',
        ])->assertSessionDoesntHaveErrors();

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::query()
            ->where('provider', Gitea::id())
            ->where('profile', 'gitea-default')
            ->firstOrFail();

        $this->assertSame(22, $sourceControl->provider_data['ssh_port']);
        $this->assertSame(22, $sourceControl->provider()->getSshPort());
    }

    public function test_edit_gitea_updates_ssh_port(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Gitea::id(),
            'user_id' => $this->user->id,
            'profile' => 'gitea',
            'provider_data' => ['token' => 'original-token', 'ssh_port' => 22],
        ]);

        $this->patch(route('source-controls.update', $sourceControl), [
            'name' => 'gitea',
            'ssh_port' => 2222,
        ])->assertSessionDoesntHaveErrors();

        $sourceControl->refresh();

        $this->assertSame(2222, $sourceControl->provider_data['ssh_port']);
        $this->assertSame('original-token', $sourceControl->provider_data['token']);
    }

    public function test_edit_cannot_clobber_token_via_extra_input(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Gitea::id(),
            'user_id' => $this->user->id,
            'profile' => 'gitea',
            'provider_data' => ['token' => 'original-token', 'ssh_port' => 22],
        ]);

        $this->patch(route('source-controls.update', $sourceControl), [
            'name' => 'gitea',
            'ssh_port' => 2222,
            'token' => 'stolen-token',
        ])->assertSessionDoesntHaveErrors();

        $sourceControl->refresh();

        $this->assertSame('original-token', $sourceControl->provider_data['token']);
        $this->assertSame(2222, $sourceControl->provider_data['ssh_port']);
    }

    public function test_edit_gitlab_cannot_clobber_token_via_extra_input(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Gitlab::id(),
            'user_id' => $this->user->id,
            'profile' => 'gitlab',
            'provider_data' => ['token' => 'original-token', 'ssh_port' => 22],
        ]);

        $this->patch(route('source-controls.update', $sourceControl), [
            'name' => 'gitlab',
            'ssh_port' => 2222,
            'token' => 'stolen-token',
        ])->assertSessionDoesntHaveErrors();

        $sourceControl->refresh();

        $this->assertSame('original-token', $sourceControl->provider_data['token']);
        $this->assertSame(2222, $sourceControl->provider_data['ssh_port']);
    }

    public function test_edit_gitea_rejects_out_of_range_ssh_port(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Gitea::id(),
            'user_id' => $this->user->id,
            'provider_data' => ['token' => 'original-token', 'ssh_port' => 22],
        ]);

        $this->patch(route('source-controls.update', $sourceControl), [
            'name' => 'gitea',
            'ssh_port' => 70000,
        ])->assertSessionHasErrors(['ssh_port']);

        $sourceControl->refresh();
        $this->assertSame(22, $sourceControl->provider_data['ssh_port']);
    }

    public function test_legacy_row_without_ssh_port_falls_back_to_22(): void
    {
        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Gitea::id(),
            'user_id' => $this->user->id,
            'provider_data' => ['token' => 'legacy-token'],
        ]);

        $this->assertSame(22, $sourceControl->provider()->getSshPort());
        $this->assertSame(22, $sourceControl->provider()->data()['ssh_port']);
    }

    public function test_clone_script_renders_custom_port(): void
    {
        $rendered = view('ssh.git.clone', [
            'host' => 'gitea.example.com',
            'repo' => 'git@gitea.example.com-site_1:owner/repo.git',
            'path' => '/home/vito/test',
            'branch' => 'main',
            'key' => 'site_1',
            'port' => 2222,
        ])->render();

        $this->assertStringContainsString('Port 2222', $rendered);
        $this->assertStringContainsString('ssh-keyscan -T 5 -p 2222 -H gitea.example.com', $rendered);
        $this->assertStringContainsString("alias_name='gitea.example.com-site_1'", $rendered);
    }

    public function test_clone_script_renders_default_port_22(): void
    {
        $rendered = view('ssh.git.clone', [
            'host' => 'gitea.example.com',
            'repo' => 'git@gitea.example.com-site_1:owner/repo.git',
            'path' => '/home/vito/test',
            'branch' => 'main',
            'key' => 'site_1',
            'port' => 22,
        ])->render();

        $this->assertStringContainsString('Port 22', $rendered);
        $this->assertStringContainsString('ssh-keyscan -T 5 -p 22 -H gitea.example.com', $rendered);
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
            [Gitlab::id(), 'https://git.example.com/', ['token' => 'test', 'ssh_port' => 2222]],
            [Gitea::id(), null, ['token' => 'test']],
            [Gitea::id(), 'https://gitea.example.com/', ['token' => 'test', 'ssh_port' => 222]],
            [Bitbucket::id(), null, ['username' => 'test', 'password' => 'test']],
            [BitbucketV2::id(), null, ['key' => 'test', 'secret' => 'test']],
        ];
    }
}
