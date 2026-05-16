<?php

namespace Tests\Feature;

use App\Models\GithubApp;
use App\Models\SourceControl;
use App\Models\User;
use App\SiteTypes\Laravel;
use App\SourceControlProviders\GithubApp as GithubAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubAppTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->admin->ensureHasDefaultProject();
    }

    public function test_non_admin_cannot_access_settings_page(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);
        $nonAdmin->ensureHasDefaultProject();

        $this->actingAs($nonAdmin)
            ->get(route('github-app'))
            ->assertNotFound();
    }

    public function test_admin_can_view_settings_page_when_no_app(): void
    {
        $this->actingAs($this->admin)
            ->get(route('github-app'))
            ->assertOk();
    }

    public function test_admin_can_view_settings_page_when_app_exists(): void
    {
        GithubApp::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('github-app'))
            ->assertOk();
    }

    public function test_manifest_callback_creates_app(): void
    {
        Http::fake([
            'api.github.com/app-manifests/test-code/conversions' => Http::response([
                'id' => 12345,
                'slug' => 'vito-test',
                'name' => 'Vito Test',
                'client_id' => 'Iv1.abc',
                'client_secret' => 'secret',
                'webhook_secret' => 'whsecret',
                'pem' => $this->generatePrivateKey(),
                'html_url' => 'https://github.com/apps/vito-test',
            ], 201),
        ]);

        $this->actingAs($this->admin)
            ->get(route('github-app.manifest-callback', ['code' => 'test-code']))
            ->assertRedirect(route('github-app'));

        $this->assertDatabaseHas('github_app', [
            'app_id' => 12345,
            'app_slug' => 'vito-test',
        ]);
    }

    public function test_manifest_callback_rejects_missing_code(): void
    {
        $this->actingAs($this->admin)
            ->get(route('github-app.manifest-callback'))
            ->assertRedirect(route('github-app'));

        $this->assertDatabaseMissing('github_app', ['app_slug' => 'vito-test']);
    }

    public function test_manual_creation_persists_app(): void
    {
        $pem = $this->generatePrivateKey();

        $this->actingAs($this->admin)
            ->post(route('github-app.manual'), [
                'app_id' => 5555,
                'name' => 'Manual Vito',
                'client_id' => 'Iv1.manualclient',
                'client_secret' => 'manualsecret',
                'webhook_secret' => 'manualwhsecret',
                'private_key' => $pem,
                'html_url' => 'https://github.com/apps/manual-vito',
            ])
            ->assertRedirect(route('github-app'));

        $this->assertDatabaseHas('github_app', [
            'app_id' => 5555,
            'app_slug' => 'manual-vito',
        ]);
    }

    public function test_manual_creation_rejects_invalid_html_url(): void
    {
        $pem = $this->generatePrivateKey();

        $this->actingAs($this->admin)
            ->post(route('github-app.manual'), [
                'app_id' => 5555,
                'client_id' => 'x',
                'client_secret' => 'x',
                'webhook_secret' => 'x',
                'private_key' => $pem,
                'html_url' => 'https://example.com/not-an-app-url',
            ])
            ->assertSessionHasErrors('html_url');
    }

    public function test_manual_creation_rejects_bad_private_key(): void
    {
        $this->actingAs($this->admin)
            ->post(route('github-app.manual'), [
                'app_id' => 5555,
                'client_id' => 'x',
                'client_secret' => 'x',
                'webhook_secret' => 'x',
                'private_key' => 'not-a-key',
                'html_url' => 'https://github.com/apps/manual-vito',
            ])
            ->assertSessionHasErrors('private_key');
    }

    public function test_install_callback_creates_source_control(): void
    {
        $app = GithubApp::factory()->create();

        Http::fake([
            'api.github.com/app/installations/999' => Http::response([
                'id' => 999,
                'html_url' => 'https://github.com/organizations/acme/settings/installations/999',
                'account' => [
                    'id' => 555,
                    'login' => 'acme',
                    'type' => 'Organization',
                ],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->get(route('github-app.install-callback', ['installation_id' => 999]))
            ->assertRedirect(route('source-controls'));

        $this->assertDatabaseHas('source_controls', [
            'provider' => SourceControl::PROVIDER_GITHUB_APP,
            'external_identifier' => '999',
            'profile' => 'acme',
        ]);

        unset($app);
    }

    public function test_install_callback_restores_soft_deleted_installation(): void
    {
        GithubApp::factory()->create();

        $existing = SourceControl::factory()->githubApp()->create([
            'external_identifier' => '777',
            'user_id' => $this->admin->id,
        ]);
        $existing->delete();
        $this->assertSoftDeleted($existing);

        Http::fake([
            'api.github.com/app/installations/777' => Http::response([
                'id' => 777,
                'html_url' => 'https://github.com/organizations/acme/settings/installations/777',
                'account' => ['id' => 1, 'login' => 'acme', 'type' => 'Organization'],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->get(route('github-app.install-callback', ['installation_id' => 777]))
            ->assertRedirect(route('source-controls'));

        $existing->refresh();
        $this->assertNull($existing->deleted_at);
    }

    public function test_sync_removes_missing_installations_and_imports_new(): void
    {
        GithubApp::factory()->create();

        $kept = SourceControl::factory()->githubApp()->create([
            'external_identifier' => '111',
            'user_id' => $this->admin->id,
        ]);
        $stale = SourceControl::factory()->githubApp()->create([
            'external_identifier' => '222',
            'user_id' => $this->admin->id,
        ]);

        Http::fake([
            'api.github.com/app/installations*' => Http::response([
                [
                    'id' => 111,
                    'html_url' => 'https://github.com/x',
                    'account' => ['id' => 11, 'login' => 'kept-org', 'type' => 'Organization'],
                ],
                [
                    'id' => 333,
                    'html_url' => 'https://github.com/y',
                    'account' => ['id' => 33, 'login' => 'new-org', 'type' => 'Organization'],
                ],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->post(route('github-app.sync'))
            ->assertRedirect(route('github-app'));

        $this->assertNotSoftDeleted($kept);
        $this->assertSoftDeleted($stale);
        $this->assertDatabaseHas('source_controls', [
            'provider' => SourceControl::PROVIDER_GITHUB_APP,
            'external_identifier' => '333',
            'profile' => 'new-org',
        ]);
    }

    public function test_cannot_manually_create_source_control_with_github_app_provider(): void
    {
        $this->actingAs($this->user)
            ->from(route('source-controls'))
            ->post(route('source-controls.store'), [
                'name' => 'fake',
                'provider' => SourceControl::PROVIDER_GITHUB_APP,
            ])
            ->assertSessionHasErrors('provider');
    }

    public function test_cannot_delete_github_app_source_control(): void
    {
        GithubApp::factory()->create();
        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('source-controls.destroy', $sc->id))
            ->assertForbidden();

        $this->assertDatabaseHas('source_controls', ['id' => $sc->id, 'deleted_at' => null]);
    }

    public function test_cannot_rename_github_app_source_control(): void
    {
        GithubApp::factory()->create();
        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->user->id,
            'profile' => 'acme',
        ]);

        $this->actingAs($this->user)
            ->patch(route('source-controls.update', $sc->id), [
                'name' => 'changed',
                'global' => true,
            ])
            ->assertRedirect();

        $sc->refresh();
        $this->assertSame('acme', $sc->profile);
        $this->assertNull($sc->project_id);
    }

    public function test_cannot_create_site_with_github_app_source_control(): void
    {
        GithubApp::factory()->create();
        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('sites.store', ['server' => $this->server]), [
                'type' => Laravel::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'repository' => 'test/test',
                'branch' => 'main',
                'composer' => true,
                'user' => 'example',
                'source_control' => $sc->id,
            ])
            ->assertSessionHasErrors('source_control');
    }

    public function test_cannot_update_site_to_use_github_app_source_control(): void
    {
        GithubApp::factory()->create();
        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->patch(route('site-settings.update-source-control', ['server' => $this->server, 'site' => $this->site]), [
                'source_control' => $sc->id,
            ])
            ->assertSessionHasErrors('source_control');
    }

    public function test_cannot_remove_github_app_when_trashed_source_control_still_has_sites(): void
    {
        GithubApp::factory()->create();

        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->admin->id,
            'external_identifier' => '999',
        ]);
        $this->site->update(['source_control_id' => $sc->id]);
        $sc->delete();

        $this->actingAs($this->admin)
            ->from(route('github-app'))
            ->delete(route('github-app.destroy'))
            ->assertSessionHasErrors('github_app');

        $this->assertDatabaseCount('github_app', 1);
        $this->assertDatabaseHas('source_controls', ['id' => $sc->id]);
    }

    public function test_remove_github_app_force_deletes_trashed_source_controls(): void
    {
        GithubApp::factory()->create();

        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->admin->id,
            'external_identifier' => '888',
        ]);
        $sc->delete();

        $this->actingAs($this->admin)
            ->delete(route('github-app.destroy'))
            ->assertRedirect(route('github-app'));

        $this->assertDatabaseMissing('source_controls', ['id' => $sc->id]);
        $this->assertDatabaseCount('github_app', 0);
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        GithubApp::factory()->create();

        $this->postJson('/api/webhooks/github-app', ['action' => 'deleted'])
            ->assertStatus(401);
    }

    public function test_webhook_handles_installation_deleted(): void
    {
        $app = GithubApp::factory()->create();
        $sc = SourceControl::factory()->githubApp()->create([
            'external_identifier' => '444',
            'user_id' => $this->admin->id,
        ]);

        $payload = json_encode([
            'action' => 'deleted',
            'installation' => ['id' => 444],
        ]);

        $sig = 'sha256='.hash_hmac('sha256', $payload, $app->webhook_secret);

        $this->call('POST', '/api/webhooks/github-app', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $sig,
            'HTTP_X_GITHUB_EVENT' => 'installation',
        ], $payload)->assertOk();

        $this->assertSoftDeleted($sc);
    }

    public function test_webhook_handles_installation_created(): void
    {
        $app = GithubApp::factory()->create();

        $payload = json_encode([
            'action' => 'created',
            'installation' => [
                'id' => 888,
                'html_url' => 'https://github.com/x',
                'account' => ['id' => 1, 'login' => 'new-acme', 'type' => 'Organization'],
            ],
        ]);

        $sig = 'sha256='.hash_hmac('sha256', $payload, $app->webhook_secret);

        $this->call('POST', '/api/webhooks/github-app', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $sig,
            'HTTP_X_GITHUB_EVENT' => 'installation',
        ], $payload)->assertOk();

        $this->assertDatabaseHas('source_controls', [
            'provider' => SourceControl::PROVIDER_GITHUB_APP,
            'external_identifier' => '888',
            'profile' => 'new-acme',
        ]);
    }

    public function test_provider_throws_for_unimplemented_methods(): void
    {
        $sc = SourceControl::factory()->githubApp()->create([
            'user_id' => $this->admin->id,
        ]);
        $provider = new GithubAppProvider($sc);

        $this->expectException(\BadMethodCallException::class);
        $provider->deployKey('t', 'r', 'k');
    }

    private function generatePrivateKey(): string
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $pem = '';
        openssl_pkey_export($res, $pem);

        return $pem;
    }
}
