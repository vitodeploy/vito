<?php

use App\Models\SourceControl;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('id returns github', function () {
    expect(Github::id())->toBe('github');
});

test('default github repo url', function () {
    $repo = 'test/repo';
    $key = 'TEST_KEY';

    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create();

    $github = new Github($sourceControlModel);

    expect($github->fullRepoUrl($repo, $key))->toBe('git@github.com-TEST_KEY:test/repo.git');
});

test('create rules returns required token', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create();

    $github = new Github($sourceControlModel);

    $rules = $github->createRules([]);

    expect($rules)->toHaveKey('token');
    expect($rules['token'])->toBe('required');
});

test('create data processes input correctly', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create();

    $github = new Github($sourceControlModel);

    $input = [
        'token' => 'my-token',
    ];

    $data = $github->createData($input);

    expect($data['token'])->toBe('my-token');
});

test('create data handles missing input', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create();

    $github = new Github($sourceControlModel);

    $data = $github->createData([]);

    expect($data['token'])->toBe('');
});

test('data retrieves stored provider data', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'stored-token',
            ],
        ]);

    $github = new Github($sourceControlModel);

    $data = $github->data();

    expect($data['token'])->toBe('stored-token');
});

test('data handles missing provider data', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [],
            'access_token' => null,
        ]);

    $github = new Github($sourceControlModel);

    $data = $github->data();

    expect($data['token'])->toBe('');
});

test('get webhook branch extracts branch from payload', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create();

    $github = new Github($sourceControlModel);

    $payload = [
        'ref' => 'refs/heads/main',
    ];

    expect($github->getWebhookBranch($payload))->toBe('main');
});

test('get webhook branch returns empty when missing', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create();

    $github = new Github($sourceControlModel);

    expect($github->getWebhookBranch([]))->toBe('');
});

test('get repos returns cached repos when cache exists', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $github = new Github($sourceControlModel);
    $cacheKey = 'github_repos_'.md5('test-token');
    $cachedRepos = ['user/repo1', 'user/repo2'];

    Cache::put($cacheKey, $cachedRepos, 900);

    $repos = $github->getRepos();

    expect($repos)->toBe($cachedRepos);
});

test('get repos fetches from api when cache missing', function () {
    Http::fake([
        'api.github.com/user/repos*' => Http::sequence()
            ->push([
                ['full_name' => 'user/repo1'],
                ['full_name' => 'user/repo2'],
            ], 200, ['Link' => '']),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $github = new Github($sourceControlModel);

    $repos = $github->getRepos(false);

    expect($repos)->toBe(['user/repo1', 'user/repo2']);
});

test('get repos returns empty array on error', function () {
    Http::fake([
        'api.github.com/user/repos*' => Http::response([], 500),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $github = new Github($sourceControlModel);

    $repos = $github->getRepos(false);

    expect($repos)->toBe([]);
});

test('get branches returns cached branches when cache exists', function () {
    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $github = new Github($sourceControlModel);
    $repo = 'user/repo';
    $cacheKey = 'github_branches_'.md5($repo.'test-token');
    $cachedBranches = ['main', 'develop'];

    Cache::put($cacheKey, $cachedBranches, 900);

    $branches = $github->getBranches($repo);

    expect($branches)->toBe($cachedBranches);
});

test('get branches fetches from api when cache missing', function () {
    Http::fake([
        'api.github.com/repos/user/repo/branches*' => Http::sequence()
            ->push([
                ['name' => 'main'],
                ['name' => 'develop'],
            ], 200, ['Link' => '']),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $github = new Github($sourceControlModel);

    $branches = $github->getBranches('user/repo', false);

    expect($branches)->toBe(['main', 'develop']);
});

test('get branches returns empty array on error', function () {
    Http::fake([
        'api.github.com/repos/user/repo/branches*' => Http::response([], 500),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->github()
        ->create([
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $github = new Github($sourceControlModel);

    $branches = $github->getBranches('user/repo', false);

    expect($branches)->toBe([]);
});
