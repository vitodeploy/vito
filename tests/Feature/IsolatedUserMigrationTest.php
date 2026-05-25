<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IsolatedUserMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_creates_iusers_for_isolated_sites_only(): void
    {
        $this->teardownIsolatedUserSchema();

        $serverA = $this->insertServer('vito');
        $serverB = $this->insertServer('deploy');
        $serverC = $this->insertServer('');

        // Server A — vito default
        $this->insertSite($serverA, null, 'a-null.test');
        $vitoOnA = $this->insertSite($serverA, 'vito', 'vito-on-a.test');
        $alpha1 = $this->insertSite($serverA, 'alpha', 'alpha-1.test');
        $alpha2 = $this->insertSite($serverA, 'alpha', 'alpha-2.test');

        // Server B — ssh_user 'deploy'; sites with user='vito' are treated as isolated.
        $this->insertSite($serverB, 'deploy', 'b-deploy.test');
        $vitoOnB = $this->insertSite($serverB, 'vito', 'vito-on-b.test');

        // Server C — empty ssh_user; falls back to config('core.ssh_user')='vito'.
        $vitoOnC = $this->insertSite($serverC, 'vito', 'vito-on-c.test');
        $betaOnC = $this->insertSite($serverC, 'beta', 'beta-1.test');

        $this->runIsolatedUserMigration();

        $iuserByKey = DB::table('isolated_users')->get()->keyBy(fn ($u) => $u->server_id.':'.$u->username);

        $this->assertCount(3, $iuserByKey, 'expected exactly alpha@A, vito@B, beta@C');
        $this->assertTrue($iuserByKey->has($serverA.':alpha'));
        $this->assertTrue($iuserByKey->has($serverB.':vito'));
        $this->assertTrue($iuserByKey->has($serverC.':beta'));

        // No iuser for the null-user, vito-on-A or vito-on-C rows.
        $this->assertFalse($iuserByKey->has($serverA.':vito'));
        $this->assertFalse($iuserByKey->has($serverC.':vito'));
        $this->assertNull(DB::table('sites')->where('id', $vitoOnA)->value('isolated_user_id'));
        $this->assertNull(DB::table('sites')->where('id', $vitoOnC)->value('isolated_user_id'));

        // FK assignment
        $this->assertSame($iuserByKey[$serverA.':alpha']->id, DB::table('sites')->where('id', $alpha1)->value('isolated_user_id'));
        $this->assertSame($iuserByKey[$serverA.':alpha']->id, DB::table('sites')->where('id', $alpha2)->value('isolated_user_id'));
        $this->assertSame($iuserByKey[$serverB.':vito']->id, DB::table('sites')->where('id', $vitoOnB)->value('isolated_user_id'));
        $this->assertSame($iuserByKey[$serverC.':beta']->id, DB::table('sites')->where('id', $betaOnC)->value('isolated_user_id'));

        // ssh_key + installed_tooling left NULL for backfilled rows.
        $this->assertNull($iuserByKey[$serverA.':alpha']->ssh_key);
        $this->assertNull($iuserByKey[$serverA.':alpha']->installed_tooling);
    }

    private function teardownIsolatedUserSchema(): void
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

    private function runIsolatedUserMigration(): void
    {
        $path = base_path('database/migrations/2026_05_24_100123_create_isolated_users_table.php');
        $migration = require $path;
        $migration->up();
    }

    private function insertServer(string $sshUser): int
    {
        static $i = 0;
        $i++;

        return DB::table('servers')->insertGetId([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
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

    private function insertSite(int $serverId, ?string $user, string $domain): int
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
}
