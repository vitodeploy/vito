<?php

use App\Models\SourceControl;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('default gitlab url', function () {
    $sourceControlModel = SourceControl::factory()
        ->gitlab()
        ->create();

    $gitlab = new Gitlab($sourceControlModel);

    expect($gitlab->getApiUrl())->toBe('https://gitlab.com/api/v4');
});

test('default gitlab repo url', function () {
    $repo = 'test/repo';
    $key = 'TEST_KEY';

    $sourceControlModel = SourceControl::factory()
        ->gitlab()
        ->create();

    $gitlab = new Gitlab($sourceControlModel);

    expect($gitlab->fullRepoUrl($repo, $key))->toBe('git@gitlab.com-TEST_KEY:test/repo.git');
});

test('custom url', function (string $url, string $expected) {
    $sourceControlModel = SourceControl::factory()
        ->gitlab()
        ->create(['url' => $url]);

    $gitlab = new Gitlab($sourceControlModel);

    expect($gitlab->getApiUrl())->toBe($expected);
})->with('customUrlData');

test('custom full repository url', function (string $url, string $expected) {
    $repo = 'test/repo';
    $key = 'TEST_KEY';

    $sourceControlModel = SourceControl::factory()
        ->gitlab()
        ->create(['url' => $url]);

    $gitlab = new Gitlab($sourceControlModel);

    expect($gitlab->fullRepoUrl($repo, $key))->toBe($expected);
})->with('customRepoUrlData');

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
        ['https://git.example.com/', 'https://git.example.com/api/v4'],
        ['https://git.test.example.com/', 'https://git.test.example.com/api/v4'],
        ['https://git.example.co.uk/', 'https://git.example.co.uk/api/v4'],
    ];
});
