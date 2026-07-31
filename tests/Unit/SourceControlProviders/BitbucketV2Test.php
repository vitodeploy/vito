<?php

use App\Models\SourceControl;
use App\SourceControlProviders\BitbucketV2;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('id returns bitbucket v2', function () {
    expect(BitbucketV2::id())->toBe('bitbucket-v2');
});

test('default bitbucket repo url', function () {
    $repo = 'test/repo';
    $key = 'TEST_KEY';

    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    expect($bitbucketV2->fullRepoUrl($repo, $key))->toBe('git@bitbucket.org-TEST_KEY:test/repo.git');
});

test('create rules returns required key and secret', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    $rules = $bitbucketV2->createRules([]);

    expect($rules)->toHaveKey('key');
    expect($rules)->toHaveKey('secret');
    expect($rules['key'])->toBe('required');
    expect($rules['secret'])->toBe('required');
});

test('create data processes input correctly', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    $input = [
        'key' => 'my-key',
        'secret' => 'my-secret',
    ];

    $data = $bitbucketV2->createData($input);

    expect($data['key'])->toBe('my-key');
    expect($data['secret'])->toBe('my-secret');
});

test('create data handles missing input', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    $data = $bitbucketV2->createData([]);

    expect($data['key'])->toBe('');
    expect($data['secret'])->toBe('');
});

test('data retrieves stored provider data', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'stored-key',
                'secret' => 'stored-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    $data = $bitbucketV2->data();

    expect($data['key'])->toBe('stored-key');
    expect($data['secret'])->toBe('stored-secret');
});

test('data handles missing provider data', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    $data = $bitbucketV2->data();

    expect($data['key'])->toBe('');
    expect($data['secret'])->toBe('');
});

test('get webhook branch extracts branch from payload', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    $payload = [
        'push' => [
            'changes' => [
                [
                    'new' => [
                        'name' => 'main',
                    ],
                ],
            ],
        ],
    ];

    expect($bitbucketV2->getWebhookBranch($payload))->toBe('main');
});

test('get webhook branch returns default when missing', function () {
    $sourceControlModel = SourceControl::factory()
        ->create([
            'provider' => BitbucketV2::id(),
            'provider_data' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
        ]);

    $bitbucketV2 = new BitbucketV2($sourceControlModel);

    expect($bitbucketV2->getWebhookBranch([]))->toBe('default-branch');
});
