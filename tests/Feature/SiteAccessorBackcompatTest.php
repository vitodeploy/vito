<?php

namespace Tests\Feature;

use App\Models\IsolatedUser;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteAccessorBackcompatTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accessor_prefers_legacy_column_over_isolated_user(): void
    {
        $iuser = IsolatedUser::factory()->create([
            'server_id' => $this->server->id,
            'username' => 'iuser-name',
        ]);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'legacy-name',
        ]);
        DB::table('sites')->where('id', $site->id)->update(['isolated_user_id' => $iuser->id]);

        $this->assertSame('legacy-name', $site->fresh()->user);
    }

    public function test_user_accessor_falls_back_to_isolated_user_when_column_empty(): void
    {
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

        $this->assertSame('iuser-name', $site->fresh()->user);
    }

    public function test_ssh_key_name_returns_site_when_legacy_column_populated(): void
    {
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

        $this->assertSame('site_'.$site->id, $site->getSshKeyName());
        $this->assertSame('LEGACY-PUBLIC-KEY', $site->ssh_key);
    }

    public function test_ssh_key_name_returns_iuser_when_no_legacy_column_and_iuser_key_set(): void
    {
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

        $this->assertSame('iuser_'.$iuser->id, $site->getSshKeyName());
        $this->assertSame('IUSER-PUBLIC-KEY', $site->ssh_key);
    }

    public function test_new_site_joining_legacy_iuser_lazy_resolves_to_iuser_key(): void
    {
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

        $this->assertSame('iuser_'.$iuser->id, $newSite->getSshKeyName(), 'new site lazy-resolves to iuser key');
        $this->assertSame('site_'.$siblingWithLegacy->id, $siblingWithLegacy->getSshKeyName(), 'legacy sibling keeps per-site key (its public key may be bound to a Git provider deploy key)');
    }
}
