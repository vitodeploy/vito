<?php

use App\Models\IsolatedUser;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('user accessor prefers legacy column over isolated user', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'iuser-name',
    ]);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'legacy-name',
    ]);
    DB::table('sites')->where('id', $site->id)->update(['isolated_user_id' => $iuser->id]);

    expect($site->fresh()->user)->toBe('legacy-name');
});

test('user accessor falls back to isolated user when column empty', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'iuser-name',
    ]);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'iuser-name',
    ]);
    DB::table('sites')->where('id', $site->id)->update([
        'isolated_user_id' => $iuser->id,
        'user' => null,
    ]);

    expect($site->fresh()->user)->toBe('iuser-name');
});

test('ssh key name returns site when legacy column populated', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'iuser-name',
        'ssh_key' => 'IUSER-PUBLIC-KEY',
    ]);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'iuser-name',
        'ssh_key' => 'LEGACY-PUBLIC-KEY',
    ]);
    DB::table('sites')->where('id', $site->id)->update(['isolated_user_id' => $iuser->id]);

    $site = $site->fresh();

    expect($site->getSshKeyName())->toBe('site_'.$site->id);
    expect($site->ssh_key)->toBe('LEGACY-PUBLIC-KEY');
});

test('ssh key name returns iuser when no legacy column and iuser key set', function () {
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'iuser-name',
        'ssh_key' => 'IUSER-PUBLIC-KEY',
    ]);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'iuser-name',
    ]);
    DB::table('sites')->where('id', $site->id)->update([
        'isolated_user_id' => $iuser->id,
        'ssh_key' => null,
    ]);

    $site = $site->fresh();

    expect($site->getSshKeyName())->toBe('iuser_'.$iuser->id);
    expect($site->ssh_key)->toBe('IUSER-PUBLIC-KEY');
});

test('new site joining legacy iuser lazy resolves to iuser key', function () {
    // Legacy iuser: backfilled from a 3.x server, ssh_key still NULL,
    // existing sibling carrying its own per-site key on disk.
    $iuser = IsolatedUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'shared',
        'ssh_key' => null,
    ]);

    $siblingWithLegacy = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'ssh_key' => 'SIBLING-LEGACY-KEY',
        'domain' => 'sibling.test',
        'path' => '/home/shared/sibling.test',
    ]);
    DB::table('sites')->where('id', $siblingWithLegacy->id)->update(['isolated_user_id' => $iuser->id]);

    $newSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'new.test',
        'path' => '/home/shared/new.test',
    ]);
    DB::table('sites')->where('id', $newSite->id)->update([
        'isolated_user_id' => $iuser->id,
        'ssh_key' => null,
    ]);

    $newSite = $newSite->fresh();
    $siblingWithLegacy = $siblingWithLegacy->fresh();

    expect($newSite->getSshKeyName())->toBe('iuser_'.$iuser->id, 'new site lazy-resolves to iuser key');
    expect($siblingWithLegacy->getSshKeyName())->toBe('site_'.$siblingWithLegacy->id, 'legacy sibling keeps per-site key (its public key may be bound to a Git provider deploy key)');
});
