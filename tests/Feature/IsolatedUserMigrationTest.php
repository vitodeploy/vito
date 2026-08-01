<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('backfill creates iusers for isolated sites only', function () {
    vitoPestFeatureIsolatedUserMigrationTestTeardownIsolatedUserSchema();

    $serverA = vitoPestFeatureIsolatedUserMigrationTestInsertServer('vito');
    $serverB = vitoPestFeatureIsolatedUserMigrationTestInsertServer('deploy');
    $serverC = vitoPestFeatureIsolatedUserMigrationTestInsertServer('');

    // Server A — vito default
    vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverA, null, 'a-null.test');
    $vitoOnA = vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverA, 'vito', 'vito-on-a.test');
    $alpha1 = vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverA, 'alpha', 'alpha-1.test');
    $alpha2 = vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverA, 'alpha', 'alpha-2.test');

    // Server B — ssh_user 'deploy'; sites with user='vito' are treated as isolated.
    vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverB, 'deploy', 'b-deploy.test');
    $vitoOnB = vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverB, 'vito', 'vito-on-b.test');

    // Server C — empty ssh_user; falls back to config('core.ssh_user')='vito'.
    $vitoOnC = vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverC, 'vito', 'vito-on-c.test');
    $betaOnC = vitoPestFeatureIsolatedUserMigrationTestInsertSite($serverC, 'beta', 'beta-1.test');

    vitoPestFeatureIsolatedUserMigrationTestRunIsolatedUserMigration();

    $iuserByKey = DB::table('isolated_users')->get()->keyBy(fn ($u) => $u->server_id.':'.$u->username);

    expect($iuserByKey)->toHaveCount(3, 'expected exactly alpha@A, vito@B, beta@C');
    expect($iuserByKey->has($serverA.':alpha'))->toBeTrue();
    expect($iuserByKey->has($serverB.':vito'))->toBeTrue();
    expect($iuserByKey->has($serverC.':beta'))->toBeTrue();

    // No iuser for the null-user, vito-on-A or vito-on-C rows.
    expect($iuserByKey->has($serverA.':vito'))->toBeFalse();
    expect($iuserByKey->has($serverC.':vito'))->toBeFalse();
    expect(DB::table('sites')->where('id', $vitoOnA)->value('isolated_user_id'))->toBeNull();
    expect(DB::table('sites')->where('id', $vitoOnC)->value('isolated_user_id'))->toBeNull();

    // FK assignment
    expect(DB::table('sites')->where('id', $alpha1)->value('isolated_user_id'))->toBe($iuserByKey[$serverA.':alpha']->id);
    expect(DB::table('sites')->where('id', $alpha2)->value('isolated_user_id'))->toBe($iuserByKey[$serverA.':alpha']->id);
    expect(DB::table('sites')->where('id', $vitoOnB)->value('isolated_user_id'))->toBe($iuserByKey[$serverB.':vito']->id);
    expect(DB::table('sites')->where('id', $betaOnC)->value('isolated_user_id'))->toBe($iuserByKey[$serverC.':beta']->id);

    // ssh_key + installed_tooling left NULL for backfilled rows.
    expect($iuserByKey[$serverA.':alpha']->ssh_key)->toBeNull();
    expect($iuserByKey[$serverA.':alpha']->installed_tooling)->toBeNull();
});

function vitoPestFeatureIsolatedUserMigrationTestTeardownIsolatedUserSchema(): void
{
    if (Schema::hasColumn('sites', 'isolated_user_id')) {
        Schema::table('sites', function ($t): void {
            $t->dropForeign(['isolated_user_id']);
            $t->dropIndex(['isolated_user_id']);
            $t->dropColumn('isolated_user_id');
        });
    }
    Schema::dropIfExists('isolated_users');
}

function vitoPestFeatureIsolatedUserMigrationTestRunIsolatedUserMigration(): void
{
    $path = base_path('database/migrations/2026_05_24_100123_create_isolated_users_table.php');
    $migration = require $path;
    $migration->up();
}

function vitoPestFeatureIsolatedUserMigrationTestInsertServer(string $sshUser): int
{
    static $i = 0;
    $i++;

    return DB::table('servers')->insertGetId([
        'project_id' => test()->server->project_id,
        'user_id' => test()->user->id,
        'name' => 'srv-'.$i,
        'ssh_user' => $sshUser,
        'ip' => '127.0.0.'.$i,
        'port' => 22,
        'os' => 'ubuntu_22',
        'provider' => 'custom',
        'provider_data' => '{}',
        'authentication' => 'X',
        'public_key' => 'pk',
        'status' => 'ready',
        'updates' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function vitoPestFeatureIsolatedUserMigrationTestInsertSite(int $serverId, ?string $user, string $domain): int
{
    return DB::table('sites')->insertGetId([
        'server_id' => $serverId,
        'type' => 'php',
        'type_data' => json_encode([]),
        'domain' => $domain,
        'web_directory' => '',
        'path' => '/home/'.($user ?? 'x').'/'.$domain,
        'php_version' => '8.2',
        'status' => 'ready',
        'progress' => 100,
        'user' => $user,
        'force_ssl' => false,
        'ssl_enabled' => false,
        'vhost_generation_enabled' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
