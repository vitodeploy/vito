<?php

use App\Models\Site;
use App\SiteTypes\BunSite;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bunSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'testuser',
        'path' => '/home/testuser/example.com',
        'type' => BunSite::id(),
        'type_data' => [
            'bun_version' => '1.2',
            'build_command' => 'bun run build',
            'start_command' => 'bun run start',
        ],
    ]);
    $this->siteType = new BunSite($this->bunSite);
});

test('id', function () {
    expect(BunSite::id())->toEqual('bun');
});

test('language', function () {
    expect($this->siteType->language())->toEqual('bun');
});

test('required services', function () {
    expect($this->siteType->requiredServices())->toEqual(['webserver', 'process_manager']);
});

test('create time tools returns bun', function () {
    expect(BunSite::createTimeTools())->toBe(['bun']);
});

test('deploy commands', function () {
    $reflection = new ReflectionMethod($this->siteType, 'deployCommands');

    expect($reflection->invoke($this->siteType))->toBe(['bun install --frozen-lockfile', 'bun run build']);
});

test('start command returns default', function () {
    $reflection = new ReflectionMethod($this->siteType, 'startCommand');

    expect($reflection->invoke($this->siteType))->toEqual('bun run start');
});

test('start command from type data', function () {
    $this->bunSite->update([
        'type_data' => array_merge($this->bunSite->type_data, [
            'start_command' => 'bun run start:prod',
        ]),
    ]);
    $siteType = new BunSite($this->bunSite->refresh());
    $reflection = new ReflectionMethod($siteType, 'startCommand');

    expect($reflection->invoke($siteType))->toEqual('bun run start:prod');
});

test('start command defaults', function () {
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'testuser',
        'path' => '/home/testuser/example.com',
        'type' => BunSite::id(),
        'type_data' => [],
    ]);
    $siteType = new BunSite($site);
    $reflection = new ReflectionMethod($siteType, 'startCommand');

    expect($reflection->invoke($siteType))->toEqual('bun run start');
});

test('data with defaults', function () {
    $data = $this->siteType->data([]);

    expect($data)->toEqual([
        'bun_version' => '1.2',
        'start_command' => 'bun run start',
    ]);
});

test('data with custom commands', function () {
    $data = $this->siteType->data([
        'bun_version' => '1.1',
        'start_command' => 'bun run start:prod',
    ]);

    expect($data)->toEqual([
        'bun_version' => '1.1',
        'start_command' => 'bun run start:prod',
    ]);
});

test('create fields', function () {
    $fields = $this->siteType->createFields([
        'source_control' => '1',
        'repository' => 'org/repo',
        'branch' => 'main',
        'port' => '3000',
    ]);

    expect($fields)->toEqual([
        'source_control_id' => '1',
        'repository' => 'org/repo',
        'branch' => 'main',
        'port' => '3000',
    ]);
});

test('create rules contain bun version', function () {
    $rules = $this->siteType->createRules([]);

    expect($rules)->toHaveKey('bun_version');
    expect($rules)->toHaveKey('source_control');
    expect($rules)->toHaveKey('repository');
    expect($rules)->toHaveKey('branch');
    expect($rules)->toHaveKey('port');
    $this->assertArrayNotHasKey('build_command', $rules);
    expect($rules)->toHaveKey('start_command');
    $this->assertArrayNotHasKey('package_manager', $rules);
});

test('base commands returns empty array', function () {
    expect($this->siteType->baseCommands())->toEqual([]);
});

test('default deployment script contains git pull then deploy commands', function () {
    $script = $this->siteType->defaultDeploymentScript();

    expect($script)->toBe("git pull origin \$BRANCH\n\nbun install --frozen-lockfile\n\nbun run build\n");
});
