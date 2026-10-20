<?php

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use App\Models\Application;
use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\BitbucketV2;
use App\SourceControlProviders\Gitea;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('connect provider', function (string $provider, ?string $customUrl, array $input) {
    $this->actingAs($this->user);

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

    $this->post(route('source-controls.store'), $input)
        ->assertSessionDoesntHaveErrors();

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
})->with('data');

test('delete provider', function (string $provider, ?string $url, array $input) {
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
})->with('data');

test('cannot delete provider', function (string $provider, ?string $url, array $input) {
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
})->with('data');

test('cannot delete source control used only by an application', function (bool $global) {
    $sourceControl = SourceControl::factory()->github()->create([
        'user_id' => $this->user->id,
        'project_id' => $global ? null : $this->server->project_id,
    ]);
    $application = Application::factory()->create([
        'project_id' => $this->server->project_id,
        'source_control_id' => $sourceControl->id,
    ]);

    $this->actingAs($this->user)->deleteJson(route('source-controls.destroy', $sourceControl))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['source_control' => 'This source control is being used by an application.']);

    $this->assertNotSoftDeleted('source_controls', ['id' => $sourceControl->id]);
    $this->assertDatabaseHas('applications', ['id' => $application->id, 'source_control_id' => $sourceControl->id]);
})->with([true, false]);

test('edit source control', function (string $provider, ?string $url, array $input) {
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

    expect($sourceControl->profile)->toEqual('new-name');
    expect($sourceControl->url)->toEqual($url);
})->with('data');

test('user cannot update other users source control', function () {
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
});

test('user cannot delete other users source control', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->delete(route('source-controls.destroy', $sourceControl))
        ->assertForbidden();
});

test('guest cannot access source controls', function () {
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
});

test('cannot manipulate user id on creation', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();

    Http::fake();

    $data = [
        'provider' => Github::id(),
        'name' => 'test',
        'token' => 'fake-token',
        'user_id' => $otherUser->id,
    ];

    $this->post(route('source-controls.store'), $data)
        ->assertSessionDoesntHaveErrors();

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
});

test('cannot transfer ownership via update', function () {
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

    expect($sourceControl->user_id)->toEqual($this->user->id);
    $this->assertNotEquals($otherUser->id, $sourceControl->user_id);
});

test('user can only see own source controls in list', function () {
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
});

test('connect gitea persists ssh port in provider data', function () {
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

    expect($sourceControl->provider_data['ssh_port'])->toBe(2222);
    expect($sourceControl->provider_data['token'])->toBe('test-token');
    expect($sourceControl->provider()->getSshPort())->toBe(2222);
});

test('connect gitea without ssh port defaults to 22', function () {
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

    expect($sourceControl->provider_data['ssh_port'])->toBe(22);
    expect($sourceControl->provider()->getSshPort())->toBe(22);
});

test('edit gitea updates ssh port', function () {
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

    expect($sourceControl->provider_data['ssh_port'])->toBe(2222);
    expect($sourceControl->provider_data['token'])->toBe('original-token');
});

test('edit cannot clobber token via extra input', function () {
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

    expect($sourceControl->provider_data['token'])->toBe('original-token');
    expect($sourceControl->provider_data['ssh_port'])->toBe(2222);
});

test('edit gitlab cannot clobber token via extra input', function () {
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

    expect($sourceControl->provider_data['token'])->toBe('original-token');
    expect($sourceControl->provider_data['ssh_port'])->toBe(2222);
});

test('edit gitea rejects out of range ssh port', function () {
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
    expect($sourceControl->provider_data['ssh_port'])->toBe(22);
});

test('legacy row without ssh port falls back to 22', function () {
    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Gitea::id(),
        'user_id' => $this->user->id,
        'provider_data' => ['token' => 'legacy-token'],
    ]);

    expect($sourceControl->provider()->getSshPort())->toBe(22);
    expect($sourceControl->provider()->data()['ssh_port'])->toBe(22);
});

test('clone script renders custom port', function () {
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
});

test('clone script renders default port 22', function () {
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
});

dataset('data', /** @return array<int, array{0: string, 1: ?string, 2: array<string, mixed>}> */ function (): array {
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
});

test('deploy key deletion is retryable and rejects provider failures without exposing responses', function (string $provider, int $status) {
    Http::preventStrayRequests();
    Http::fake([
        'bitbucket.org/site/oauth2/access_token' => Http::response(['access_token' => 'token'], 200),
        '*' => Http::response(['message' => 'SECRET'], $status),
    ]);
    $sourceControl = SourceControl::factory()->create(['provider' => $provider]);
    if ($status === 500) {
        expect(fn () => $sourceControl->provider()->deleteDeployKey('123', 'owner/repository'))
            ->toThrow(RuntimeException::class, 'Unable to remove the source-control deploy key.');
    } else {
        $sourceControl->provider()->deleteDeployKey('123', 'owner/repository');
        Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/123'));
    }
})->with(['github', 'gitlab', 'gitea', 'bitbucket', 'bitbucket-v2'])->with([204, 404, 500]);

test('hook deletion accepts already absent hooks and propagates other provider failures', function (string $provider, int $status) {
    Http::preventStrayRequests();
    Http::fake([
        'bitbucket.org/site/oauth2/access_token' => Http::response(['access_token' => 'token']),
        '*' => Http::response([], $status),
    ]);
    $source = SourceControl::factory()->create(['provider' => $provider])->provider();
    if (in_array($status, [204, 404], true)) {
        $source->destroyHook('owner/repository', '123');
    } else {
        expect(fn () => $source->destroyHook('owner/repository', '123'))->toThrow(FailedToDestroyGitHook::class);
    }
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/hooks/123'));
})->with(['github', 'gitlab', 'gitea', 'bitbucket', 'bitbucket-v2'])->with([204, 404, 403, 500]);

test('deploy key registration recovers existing keys after an ambiguous response', function (string $provider) {
    Http::preventStrayRequests();
    Http::fake(function ($request) use ($provider) {
        if (str_contains($request->url(), 'oauth2')) {
            return Http::response(['access_token' => 'token'], 200);
        }
        if ($request->method() === 'POST') {
            return Http::response(['message' => 'already registered'], 422);
        }
        $keys = [['id' => 321, 'key' => 'ssh-rsa unique-public-key existing-comment']];

        return Http::response(str_starts_with($provider, 'bitbucket') ? ['values' => $keys] : $keys);
    });
    $sourceControl = SourceControl::factory()->create(['provider' => $provider]);
    expect($sourceControl->provider()->deployKey('component_123', 'owner/repository', 'ssh-rsa unique-public-key'))->toBe('321');
})->with(['github', 'gitlab', 'gitea', 'bitbucket', 'bitbucket-v2']);

test('deploy key registration sanitizes errors and does not accept empty key ids', function (string $provider) {
    Http::preventStrayRequests();
    Http::fake([
        'bitbucket.org/site/oauth2/access_token' => Http::response(['access_token' => 'token'], 200),
        '*' => Http::response(['message' => 'SECRET'], 500),
    ]);
    $sourceControl = SourceControl::factory()->create(['provider' => $provider]);
    expect(fn () => $sourceControl->provider()->deployKey('component_123', 'owner/repository', 'ssh-rsa unique-public-key'))
        ->toThrow(FailedToDeployGitKey::class, 'Unable to register the source-control deploy key.');
})->with(['github', 'gitlab', 'gitea', 'bitbucket', 'bitbucket-v2']);

test('deploy key recovery scans capped pages and fails safely if no key matches', function (bool $match, string $providerId) {
    Http::fake(function ($request) use ($match, $providerId) {
        if ($request->method() === 'POST') {
            return Http::response([], 201);
        }
        if ($request['page'] === 1) {
            return Http::response(array_fill(0, $providerId === 'gitea' ? 50 : 100, ['id' => 1, 'key' => 'ssh-rsa another-key']));
        }

        return Http::response($match ? [['id' => 321, 'key' => 'ssh-rsa unique-public-key']] : []);
    });
    $provider = SourceControl::factory()->create(['provider' => $providerId])->provider();
    if ($match) {
        expect($provider->deployKey('component_123', 'owner/repository', 'ssh-rsa unique-public-key'))->toBe('321');
    } else {
        expect(fn () => $provider->deployKey('component_123', 'owner/repository', 'ssh-rsa unique-public-key'))
            ->toThrow(FailedToDeployGitKey::class);
    }
    Http::assertSentCount(3);
})->with([false, true])->with(['github', 'gitea']);

test('deploy key registration reconciles lost post responses', function (string $provider) {
    Http::fake(function ($request) use ($provider) {
        if (str_contains($request->url(), 'oauth2')) {
            return Http::response(['access_token' => 'token']);
        }
        if ($request->method() === 'POST') {
            throw new ConnectionException('SECRET');
        }
        $keys = [['id' => 987, 'key' => 'ssh-rsa lost-response-key']];

        return Http::response(str_starts_with($provider, 'bitbucket') ? ['values' => $keys] : $keys);
    });
    expect(SourceControl::factory()->create(['provider' => $provider])->provider()
        ->deployKey('component_123', 'owner/repository', 'ssh-rsa lost-response-key', true))->toBe('987');
})->with(['github', 'gitlab', 'gitea', 'bitbucket', 'bitbucket-v2']);

test('component keys are read only while legacy site key permissions are preserved', function (string $provider, bool $readOnly) {
    Http::fake(['*' => Http::response(['id' => 123], 201)]);
    SourceControl::factory()->create(['provider' => $provider])->provider()->deployKey('test', 'owner/repository', 'ssh-rsa key', $readOnly);

    Http::assertSent(fn ($request) => $provider === 'github' ? $request['read_only'] === $readOnly : $request['can_push'] === ! $readOnly);
})->with(['github', 'gitlab'])->with([false, true]);

test('bitbucket oauth errors cannot escape deploy key sanitization or enter logs', function () {
    Log::spy();
    Http::fake(['*' => Http::response(['error_description' => 'SECRET'], 401)]);
    expect(fn () => SourceControl::factory()->create(['provider' => 'bitbucket-v2'])->provider()->deployKey('test', 'owner/repository', 'ssh-rsa key'))
        ->toThrow(FailedToDeployGitKey::class, 'Unable to register the source-control deploy key.');
    Log::shouldHaveReceived('error')->once()->with('Failed to get Bitbucket access token with client credentials', ['status' => 401]);
});

test('deploy key lookup never treats malformed responses as confirmed absence', function (mixed $body) {
    Http::fake(['*' => Http::response($body)]);
    expect(fn () => SourceControl::factory()->create()->provider()->findDeployKey('owner/repository', 'ssh-rsa public-key'))
        ->toThrow(FailedToDeployGitKey::class, 'Unable to look up the source-control deploy key.');
})->with([
    'invalid JSON' => ['invalid JSON'],
    'missing id' => [[['key' => 'ssh-rsa public-key']]],
    'missing key' => [[['id' => 123]]],
]);

test('deploy key lookup rejects missing public keys before any request', function () {
    Http::fake();
    expect(fn () => SourceControl::factory()->create()->provider()->findDeployKey('owner/repository', ''))
        ->toThrow(FailedToDeployGitKey::class);
    Http::assertNothingSent();
});
