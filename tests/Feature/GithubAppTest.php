<?php

use App\Facades\SSH;
use App\Models\GitHook;
use App\Models\GithubApp;
use App\Models\SourceControl;
use App\Models\User;
use App\SiteTypes\Laravel;
use App\SourceControlProviders\GithubApp as GithubAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->admin->ensureHasDefaultProject();
});

test('non admin cannot access settings page', function () {
    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $nonAdmin->ensureHasDefaultProject();

    $this->actingAs($nonAdmin)
        ->get(route('github-app'))
        ->assertNotFound();
});

test('admin can view settings page when no app', function () {
    $this->actingAs($this->admin)
        ->get(route('github-app'))
        ->assertOk();
});

test('admin can view settings page when app exists', function () {
    GithubApp::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('github-app'))
        ->assertOk();
});

test('manifest callback creates app', function () {
    Http::fake([
        'api.github.com/app-manifests/test-code/conversions' => Http::response([
            'id' => 12345,
            'slug' => 'vito-test',
            'name' => 'Vito Test',
            'client_id' => 'Iv1.abc',
            'client_secret' => 'secret',
            'webhook_secret' => 'whsecret',
            'pem' => vitoPestFeatureGithubAppTestGeneratePrivateKey(),
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

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.github.com/app-manifests/test-code/conversions'
            && $request->body() === '';
    });
});

test('manifest callback rejects missing code', function () {
    $this->actingAs($this->admin)
        ->get(route('github-app.manifest-callback'))
        ->assertRedirect(route('github-app'));

    $this->assertDatabaseMissing('github_app', ['app_slug' => 'vito-test']);
});

test('manual creation persists app', function () {
    $pem = vitoPestFeatureGithubAppTestGeneratePrivateKey();

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
});

test('manual creation rejects invalid html url', function () {
    $pem = vitoPestFeatureGithubAppTestGeneratePrivateKey();

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
});

test('manual creation rejects bad private key', function () {
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
});

test('install callback creates source control', function () {
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
});

test('install callback restores soft deleted installation', function () {
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
    expect($existing->deleted_at)->toBeNull();
});

test('sync removes missing installations and imports new', function () {
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
});

test('cannot manually create source control with github app provider', function () {
    $this->actingAs($this->user)
        ->from(route('source-controls'))
        ->post(route('source-controls.store'), [
            'name' => 'fake',
            'provider' => SourceControl::PROVIDER_GITHUB_APP,
        ])
        ->assertSessionHasErrors('provider');
});

test('cannot delete github app source control', function () {
    GithubApp::factory()->create();
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->delete(route('source-controls.destroy', $sc->id))
        ->assertForbidden();

    $this->assertDatabaseHas('source_controls', ['id' => $sc->id, 'deleted_at' => null]);
});

test('cannot rename github app source control', function () {
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
    expect($sc->profile)->toBe('acme');
    expect($sc->project_id)->toBeNull();
});

test('can create site with github app source control', function () {
    SSH::fake();
    GithubApp::factory()->create();
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'api.github.com/repos/test/test' => Http::response(['full_name' => 'test/test'], 200),
        'api.github.com/repos/test/test/commits/main' => Http::response([], 200),
        'api.github.com/app/installations/*/access_tokens' => Http::response(['token' => 'ghs_TESTTOKEN'], 201),
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
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'example.com',
        'source_control_id' => $sc->id,
    ]);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/keys'));
});

test('can update site to use github app source control', function () {
    SSH::fake();
    GithubApp::factory()->create();
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'api.github.com/repos/*' => Http::response(['full_name' => 'test/test'], 200),
        'api.github.com/app/installations/*/access_tokens' => Http::response(['token' => 'ghs_TESTTOKEN'], 201),
    ]);

    $this->actingAs($this->user)
        ->patch(route('site-settings.update-source-control', ['server' => $this->server, 'site' => $this->site]), [
            'source_control' => $sc->id,
        ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'id' => $this->site->id,
        'source_control_id' => $sc->id,
    ]);
});

test('cannot remove github app when trashed source control still has sites', function () {
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
});

test('remove github app force deletes trashed source controls', function () {
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
});

test('webhook rejects bad signature', function () {
    GithubApp::factory()->create();

    $this->postJson('/api/webhooks/github-app', ['action' => 'deleted'])
        ->assertStatus(401);
});

test('webhook handles installation deleted', function () {
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
});

test('webhook handles installation created', function () {
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
});

test('provider implements deployment surface', function () {
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->admin->id,
    ]);
    $provider = new GithubAppProvider($sc);

    expect($provider->fullRepoUrl('owner/repo', 'irrelevant-key'))->toBe('https://github.com/owner/repo.git');

    expect($provider->deployKey('t', 'owner/repo', 'public-key'))->toBe('');
    $provider->deleteDeployKey('deploy-key-id', 'owner/repo');

    $hookResult = $provider->deployHook('owner/repo', ['push'], 'secret');
    expect($hookResult['hook_id'])->toBe('app-installation');
    expect($hookResult['hook_response']['source'])->toBe('github-app-installation');

    $provider->destroyHook('owner/repo', 'app-installation');
});

test('environment variables include git http token for github app sites', function () {
    GithubApp::factory()->create();
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->user->id,
        'external_identifier' => '12345',
    ]);
    $this->site->update(['source_control_id' => $sc->id]);
    $this->site->refresh();

    Cache::put('github_app_token_12345', 'ghs_CACHEDTOKEN', 60);

    $vars = $this->site->environmentVariables();

    expect($vars)->toHaveKey('GIT_HTTP_TOKEN');
    expect($vars['GIT_HTTP_TOKEN'])->toBe('ghs_CACHEDTOKEN');
});

test('environment variables omit git http token for non github app sites', function () {
    $vars = $this->site->environmentVariables();

    $this->assertArrayNotHasKey('GIT_HTTP_TOKEN', $vars);
});

test('webhook handles push event triggers deploy', function () {
    SSH::fake();
    $app = GithubApp::factory()->create();
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->user->id,
        'external_identifier' => '555',
    ]);
    $this->site->update([
        'source_control_id' => $sc->id,
        'repository' => 'acme/widgets',
        'branch' => 'main',
    ]);
    $this->site->deploymentScript->update(['content' => 'echo deploying']);
    GitHook::factory()->create([
        'site_id' => $this->site->id,
        'source_control_id' => $sc->id,
        'events' => ['push'],
        'actions' => ['deploy'],
        'hook_id' => 'app-installation',
    ]);

    Http::fake([
        'api.github.com/repos/acme/widgets' => Http::response(['full_name' => 'acme/widgets'], 200),
        'api.github.com/repos/acme/widgets/commits/main' => Http::response([
            'sha' => 'abc123',
            'commit' => ['committer' => ['name' => 'a', 'email' => 'a@b'], 'message' => 'm'],
            'html_url' => 'https://github.com/x',
        ], 200),
        'api.github.com/app/installations/*/access_tokens' => Http::response(['token' => 'ghs_TESTTOKEN'], 201),
    ]);

    $payload = json_encode([
        'ref' => 'refs/heads/main',
        'installation' => ['id' => 555],
        'repository' => ['full_name' => 'acme/widgets'],
    ]);
    $sig = 'sha256='.hash_hmac('sha256', $payload, $app->webhook_secret);

    $this->call('POST', '/api/webhooks/github-app', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => $sig,
        'HTTP_X_GITHUB_EVENT' => 'push',
    ], $payload)->assertOk();

    $this->assertDatabaseHas('deployments', [
        'site_id' => $this->site->id,
        'commit_id' => 'abc123',
    ]);
});

test('webhook push event for unmatched branch does not deploy', function () {
    $app = GithubApp::factory()->create();
    $sc = SourceControl::factory()->githubApp()->create([
        'user_id' => $this->user->id,
        'external_identifier' => '666',
    ]);
    $this->site->update([
        'source_control_id' => $sc->id,
        'repository' => 'acme/widgets',
        'branch' => 'main',
    ]);
    GitHook::factory()->create([
        'site_id' => $this->site->id,
        'source_control_id' => $sc->id,
        'events' => ['push'],
        'actions' => ['deploy'],
    ]);

    $payload = json_encode([
        'ref' => 'refs/heads/develop',
        'installation' => ['id' => 666],
        'repository' => ['full_name' => 'acme/widgets'],
    ]);
    $sig = 'sha256='.hash_hmac('sha256', $payload, $app->webhook_secret);

    $this->call('POST', '/api/webhooks/github-app', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => $sig,
        'HTTP_X_GITHUB_EVENT' => 'push',
    ], $payload)->assertOk();

    $this->assertDatabaseMissing('deployments', [
        'site_id' => $this->site->id,
    ]);
});

function vitoPestFeatureGithubAppTestGeneratePrivateKey(): string
{
    $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $pem = '';
    openssl_pkey_export($res, $pem);

    return $pem;
}
