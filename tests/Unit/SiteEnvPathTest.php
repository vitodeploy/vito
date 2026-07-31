<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function vitoPestUnitSiteEnvPathTestSite(?string $storedEnvPath = null): Site
{
    return Site::factory()->make([
        'path' => '/home/vito/example.com',
        'type_data' => $storedEnvPath === null ? null : ['env_path' => $storedEnvPath],
    ]);
}

/**
 * @return array<string, array<int, string>>
 */
dataset('rejectedPathProvider', function () {
    return [
        'outside the site directory' => ['/etc/passwd'],
        'shell metacharacters' => ['/etc/passwd;id'],
        'command substitution' => ['/home/vito/example.com/$(id)'],
        'traversal' => ['/home/vito/example.com/../other/.env'],
        'trailing newline' => ["/home/vito/example.com/.env\n"],
        'empty string' => [''],
        'sibling directory sharing a prefix' => ['/home/vito/example.com-evil/.env'],
    ];
});

test('it rejects paths outside the site', function (string $path) {
    $this->expectException(ValidationException::class);

    vitoPestUnitSiteEnvPathTestSite()->resolveEnvPath($path);
})->with('rejectedPathProvider');

test('it accepts a path inside the site', function () {
    expect(vitoPestUnitSiteEnvPathTestSite()->resolveEnvPath('/home/vito/example.com/nested/.env'))->toEqual('/home/vito/example.com/nested/.env');
});

test('it defaults to the site env file', function () {
    expect(vitoPestUnitSiteEnvPathTestSite()->resolveEnvPath())->toEqual('/home/vito/example.com/.env');
});

test('it exempts the stored path and stays idempotent', function () {
    $site = vitoPestUnitSiteEnvPathTestSite('/home/vito/legacy path/.env');

    $resolved = $site->resolveEnvPath();

    expect($resolved)->toEqual('/home/vito/legacy path/.env');
    expect($site->resolveEnvPath($resolved))->toEqual($resolved);
});
