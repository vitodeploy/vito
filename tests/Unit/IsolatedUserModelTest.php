<?php

use App\Models\IsolatedUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('tooling version round trips via set and get', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'alpha',
    ]);

    expect($iuser->toolingVersion('node'))->toBeNull();

    $iuser->setToolingVersion('node', '22');

    expect($iuser->toolingVersion('node'))->toBe('22');
    expect($iuser->fresh()->toolingVersion('node'))->toBe('22');
});

test('tooling status round trips', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'alpha',
    ]);

    $iuser->setToolingStatus('bun', 'installing');
    expect($iuser->fresh()->toolingStatus('bun'))->toBe('installing');

    $iuser->setToolingStatus('bun', null);
    expect($iuser->fresh()->toolingStatus('bun'))->toBeNull();
});

test('set version and status for different tools does not clobber each other', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'alpha',
    ]);

    $iuser->setToolingVersion('node', '22');
    $iuser->setToolingVersion('bun', '1.2');
    $iuser->setToolingStatus('node', 'installing');

    $fresh = $iuser->fresh();
    expect($fresh->toolingVersion('node'))->toBe('22');
    expect($fresh->toolingStatus('node'))->toBe('installing');
    expect($fresh->toolingVersion('bun'))->toBe('1.2');
    expect($fresh->toolingStatus('bun'))->toBeNull();
});

test('clear tooling removes only the named tool', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'alpha',
    ]);

    $iuser->setToolingVersion('node', '22');
    $iuser->setToolingVersion('bun', '1.2');

    $iuser->clearTooling('node');

    $fresh = $iuser->fresh();
    expect($fresh->toolingVersion('node'))->toBeNull();
    expect($fresh->toolingVersion('bun'))->toBe('1.2');
});

test('lock uses legacy key shape for cross deploy compat', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'alpha',
    ]);

    $lock = $iuser->lock();
    expect($lock->get())->toBeTrue('iuser lock should be acquirable initially');

    $legacyClash = Cache::lock("isolate:{$this->server->id}:alpha", 60);
    expect($legacyClash->get())->toBeFalse('legacy-shaped lock should clash with the iuser lock');

    $lock->release();
});
