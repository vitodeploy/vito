<?php

use App\Models\Site;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function vitoPestUnitNodeSiteTestSiteType(string $packageManager): NodeSite
{
    $site = Site::factory()->create([
        'server_id' => test()->server->id,
        'user' => 'testuser',
        'path' => '/home/testuser/example.com',
        'type' => NodeSite::id(),
        'type_data' => [
            'node_version' => '22',
            'package_manager' => $packageManager,
        ],
    ]);

    return new NodeSite($site);
}

test('id and language', function () {
    expect(NodeSite::id())->toBe('node');
    expect(vitoPestUnitNodeSiteTestSiteType('npm')->language())->toBe('nodejs');
});

test('deploy commands for npm', function () {
    $siteType = vitoPestUnitNodeSiteTestSiteType('npm');
    $reflection = new ReflectionMethod($siteType, 'deployCommands');

    expect($reflection->invoke($siteType))->toBe(['npm ci', 'npm run build']);
});

test('deploy commands for pnpm', function () {
    $siteType = vitoPestUnitNodeSiteTestSiteType('pnpm');
    $reflection = new ReflectionMethod($siteType, 'deployCommands');

    expect($reflection->invoke($siteType))->toBe(['pnpm install --frozen-lockfile', 'pnpm run build']);
});

test('deploy commands for yarn', function () {
    $siteType = vitoPestUnitNodeSiteTestSiteType('yarn');
    $reflection = new ReflectionMethod($siteType, 'deployCommands');

    expect($reflection->invoke($siteType))->toBe(['yarn install --frozen-lockfile', 'yarn build']);
});

test('default deployment script for pnpm', function () {
    $script = vitoPestUnitNodeSiteTestSiteType('pnpm')->defaultDeploymentScript();

    expect($script)->toBe("git pull origin \$BRANCH\n\npnpm install --frozen-lockfile\n\npnpm run build\n");
});
