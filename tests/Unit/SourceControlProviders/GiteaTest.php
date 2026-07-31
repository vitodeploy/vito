<?php

use App\Models\SourceControl;
use App\SourceControlProviders\Gitea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('id returns gitea', function () {
    expect(Gitea::id())->toBe('gitea');
});

test('default gitea url', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    expect($gitea->getApiUrl())->toBe('https://gitea.com/api/v1');
});

test('default gitea repo url', function () {
    $repo = 'test/repo';
    $key = 'TEST_KEY';

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    expect($gitea->fullRepoUrl($repo, $key))->toBe('git@gitea.com-TEST_KEY:test/repo.git');
});

test('custom url', function (string $url, string $expected) {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'url' => $url,
        ]);

    $gitea = new Gitea($sourceControlModel);

    expect($gitea->getApiUrl())->toBe($expected);
})->with('customUrlData');

test('custom full repository url', function (string $url, string $expected) {
    $repo = 'test/repo';
    $key = 'TEST_KEY';

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'url' => $url,
        ]);

    $gitea = new Gitea($sourceControlModel);

    expect($gitea->fullRepoUrl($repo, $key))->toBe($expected);
})->with('customRepoUrlData');

test('create rules returns required token and optional url', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    $rules = $gitea->createRules([]);

    expect($rules)->toHaveKey('token');
    expect($rules['token'])->toBe('required');
    expect($rules)->toHaveKey('url');
    expect($rules['url'])->toBeArray();
    expect($rules['url'])->toContain('nullable');
    expect($rules['url'])->toContain('url:http,https');
    expect($rules['url'])->toContain('ends_with:/');
});

test('create data processes input correctly', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    $input = [
        'token' => 'my-token',
        'url' => 'https://git.example.com/',
    ];

    $data = $gitea->createData($input);

    expect($data['token'])->toBe('my-token');
});

test('create data handles missing input', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    $data = $gitea->createData([]);

    expect($data['token'])->toBe('');
});

test('data retrieves stored provider data', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'stored-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);

    $data = $gitea->data();

    expect($data['token'])->toBe('stored-token');
});

test('data handles missing provider data', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [],
            'access_token' => null,
        ]);

    $gitea = new Gitea($sourceControlModel);

    $data = $gitea->data();

    expect($data['token'])->toBe('');
});

test('get webhook branch extracts branch from payload', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    $payload = [
        'ref' => 'refs/heads/main',
    ];

    expect($gitea->getWebhookBranch($payload))->toBe('main');
});

test('get webhook branch returns empty when missing', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
        ]);

    $gitea = new Gitea($sourceControlModel);

    expect($gitea->getWebhookBranch([]))->toBe('');
});

test('get repos returns cached repos when cache exists', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);
    $cacheKey = 'gitea_repos_'.md5($gitea->getApiUrl().'test-token');
    $cachedRepos = ['user/repo1', 'user/repo2'];

    Cache::put($cacheKey, $cachedRepos, 900);

    $repos = $gitea->getRepos();

    expect($repos)->toBe($cachedRepos);
});

test('get repos fetches from api when cache missing', function () {
    Http::fake([
        'gitea.com/api/v1/user/repos*' => Http::response([
            ['full_name' => 'user/repo1'],
            ['full_name' => 'user/repo2'],
        ], 200),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);

    $repos = $gitea->getRepos(false);

    expect($repos)->toBe(['user/repo1', 'user/repo2']);
});

test('get repos returns empty array on error', function () {
    Http::fake([
        'gitea.com/api/v1/user/repos*' => Http::response([], 500),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);

    $repos = $gitea->getRepos(false);

    expect($repos)->toBe([]);
});

test('get branches returns cached branches when cache exists', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);
    $repo = 'user/repo';
    $cacheKey = 'gitea_branches_'.md5($repo.$gitea->getApiUrl().'test-token');
    $cachedBranches = ['main', 'develop'];

    Cache::put($cacheKey, $cachedBranches, 900);

    $branches = $gitea->getBranches($repo);

    expect($branches)->toBe($cachedBranches);
});

test('get branches fetches from api when cache missing', function () {
    Http::fake([
        'gitea.com/api/v1/repos/user/repo/branches*' => Http::response([
            ['name' => 'main'],
            ['name' => 'develop'],
        ], 200),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);

    $branches = $gitea->getBranches('user/repo', false);

    expect($branches)->toBe(['main', 'develop']);
});

test('get branches returns empty array on error', function () {
    Http::fake([
        'gitea.com/api/v1/repos/user/repo/branches*' => Http::response([], 500),
    ]);

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => Gitea::id(),
            'provider_data' => [
                'token' => 'test-token',
            ],
        ]);

    $gitea = new Gitea($sourceControlModel);

    $branches = $gitea->getBranches('user/repo', false);

    expect($branches)->toBe([]);
});

/**
 * @return array<int, array<int, string>>
 */
dataset('customRepoUrlData', function () {
    return [
        ['https://git.example.com/', 'git@git.example.com-TEST_KEY:test/repo.git'],
        ['https://git.test.example.com/', 'git@git.test.example.com-TEST_KEY:test/repo.git'],
        ['https://git.example.co.uk/', 'git@git.example.co.uk-TEST_KEY:test/repo.git'],
    ];
});

/**
 * @return array<int, array<int, string>>
 */
dataset('customUrlData', function () {
    return [
        ['https://git.example.com/', 'https://git.example.com/api/v1'],
        ['https://git.test.example.com/', 'https://git.test.example.com/api/v1'],
        ['https://git.example.co.uk/', 'https://git.example.co.uk/api/v1'],
    ];
});
