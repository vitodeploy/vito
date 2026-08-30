<?php

use App\Exceptions\FailedToDeleteDeployKey;
use App\Models\SourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\BitbucketV2;
use App\SourceControlProviders\Gitea;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use App\SourceControlProviders\SourceControlProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

dataset('deployKeyDeletionProviders', [
    'github' => [
        Github::class,
        ['provider' => Github::id()],
        'https://api.github.com/repos/*/keys/123',
    ],
    'gitlab' => [
        Gitlab::class,
        ['provider' => Gitlab::id()],
        'https://gitlab.com/api/v4/projects/*/deploy_keys/123',
    ],
    'gitea' => [
        Gitea::class,
        ['provider' => Gitea::id()],
        'https://gitea.com/api/v1/repos/*/keys/123',
    ],
    'bitbucket' => [
        Bitbucket::class,
        [
            'provider' => Bitbucket::id(),
            'provider_data' => ['username' => 'test', 'password' => 'test'],
        ],
        'https://api.bitbucket.org/2.0/repositories/*/deploy-keys/123',
    ],
    'bitbucket v2' => [
        BitbucketV2::class,
        [
            'provider' => BitbucketV2::id(),
            'provider_data' => ['key' => 'test', 'secret' => 'test'],
        ],
        'https://api.bitbucket.org/2.0/repositories/*/deploy-keys/123',
    ],
]);

test('deploy key deletion failures bubble up', function (string $provider, array $attributes, string $endpoint) {
    Http::fake([
        'https://bitbucket.org/site/oauth2/access_token' => Http::response(['access_token' => 'test'], 200),
        $endpoint => Http::response([], 401),
    ]);

    $sourceControl = SourceControl::factory()->create($attributes);
    /** @var SourceControlProvider $handler */
    $handler = new $provider($sourceControl);

    expect(fn () => $handler->deleteDeployKey('123', 'organization/repository'))
        ->toThrow(FailedToDeleteDeployKey::class);
})->with('deployKeyDeletionProviders');

test('deploy key deletion connection failures bubble up', function (string $provider, array $attributes, string $endpoint) {
    Http::fake([
        'https://bitbucket.org/site/oauth2/access_token' => Http::response(['access_token' => 'test'], 200),
        $endpoint => Http::failedConnection(),
    ]);

    $sourceControl = SourceControl::factory()->create($attributes);
    /** @var SourceControlProvider $handler */
    $handler = new $provider($sourceControl);

    expect(fn () => $handler->deleteDeployKey('123', 'organization/repository'))
        ->toThrow(FailedToDeleteDeployKey::class);
})->with('deployKeyDeletionProviders');

test('missing deploy key is already deleted', function () {
    Http::fake([
        'https://api.github.com/repos/*/keys/123' => Http::response([], 404),
    ]);

    $sourceControl = SourceControl::factory()->github()->create();
    $provider = new Github($sourceControl);

    $provider->deleteDeployKey('123', 'organization/repository');

    Http::assertSentCount(1);
});
