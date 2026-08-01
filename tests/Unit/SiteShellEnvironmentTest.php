<?php

use App\Helpers\SiteShellEnvironment;
use App\Models\Site;
use App\SiteTypes\Laravel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('collect returns empty when no tools are installed', function () {
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-empty',
        'path' => '/home/isolated-empty/site.test',
        'type' => Laravel::id(),
        'type_data' => [],
    ]);

    expect(SiteShellEnvironment::collect($site))->toBe([]);
});

test('collect returns mise shims on path when a tool is installed', function () {
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-node',
        'path' => '/home/isolated-node/site.test',
        'type' => Laravel::id(),
        'type_data' => [],
    ]);
    $site->isolatedUser->setToolingVersion('node', '22');

    $env = SiteShellEnvironment::collect($site->refresh());

    expect($env)->toHaveKey('PATH');
    expect($env['PATH'])->toStartWith('/home/isolated-node/.local/share/mise/shims:');
    $this->assertStringContainsString('/usr/local/bin', $env['PATH']);
    $this->assertStringContainsString('/home/isolated-node/.local/bin', $env['PATH']);
});

test('collect dedupes path entries from multiple mise tools', function () {
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-multi',
        'path' => '/home/isolated-multi/site.test',
        'type' => Laravel::id(),
        'type_data' => [],
    ]);
    $site->isolatedUser->setToolingVersion('node', '22');
    $site->isolatedUser->setToolingVersion('bun', '1.2');
    $site->isolatedUser->setToolingVersion('pnpm', '9');

    $env = SiteShellEnvironment::collect($site->refresh());

    expect($env)->toHaveKey('PATH');
    $occurrences = substr_count($env['PATH'], '/home/isolated-multi/.local/share/mise/shims');
    expect($occurrences)->toBe(1, 'Mise shims path should appear exactly once.');
});

test('collect returns empty when site user is empty', function () {
    $site = new Site(['user' => '']);

    expect(SiteShellEnvironment::collect($site))->toBe([]);
});

test('wrap builds bash invocation with env and optional cd', function () {
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-wrap',
        'path' => '/home/isolated-wrap/site.test',
        'type' => Laravel::id(),
        'type_data' => [],
    ]);
    $site->isolatedUser->setToolingVersion('node', '22');
    $site->refresh();

    $withoutCd = SiteShellEnvironment::wrap($site, 'node -v');
    expect($withoutCd)->toStartWith("bash -c '");
    $this->assertStringContainsString('export PATH=', $withoutCd);
    $this->assertStringContainsString('node -v', $withoutCd);
    $this->assertStringNotContainsString('cd ', $withoutCd);

    $withCd = SiteShellEnvironment::wrap($site, 'npm install', true);
    $this->assertStringContainsString('cd ', $withCd);
    $this->assertStringContainsString('isolated-wrap/site.test', $withCd);
    $this->assertStringContainsString('npm install', $withCd);
});
