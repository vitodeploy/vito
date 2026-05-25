<?php

namespace Tests\Unit;

use App\Models\IsolatedUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IsolatedUserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tooling_version_round_trips_via_set_and_get(): void
    {
        $iuser = IsolatedUser::factory()->create([
            'server_id' => $this->server->id,
            'username' => 'alpha',
        ]);

        $this->assertNull($iuser->toolingVersion('node'));

        $iuser->setToolingVersion('node', '22');

        $this->assertSame('22', $iuser->toolingVersion('node'));
        $this->assertSame('22', $iuser->fresh()->toolingVersion('node'));
    }

    public function test_tooling_status_round_trips(): void
    {
        $iuser = IsolatedUser::factory()->create([
            'server_id' => $this->server->id,
            'username' => 'alpha',
        ]);

        $iuser->setToolingStatus('bun', 'installing');
        $this->assertSame('installing', $iuser->fresh()->toolingStatus('bun'));

        $iuser->setToolingStatus('bun', null);
        $this->assertNull($iuser->fresh()->toolingStatus('bun'));
    }

    public function test_set_version_and_status_for_different_tools_does_not_clobber_each_other(): void
    {
        $iuser = IsolatedUser::factory()->create([
            'server_id' => $this->server->id,
            'username' => 'alpha',
        ]);

        $iuser->setToolingVersion('node', '22');
        $iuser->setToolingVersion('bun', '1.2');
        $iuser->setToolingStatus('node', 'installing');

        $fresh = $iuser->fresh();
        $this->assertSame('22', $fresh->toolingVersion('node'));
        $this->assertSame('installing', $fresh->toolingStatus('node'));
        $this->assertSame('1.2', $fresh->toolingVersion('bun'));
        $this->assertNull($fresh->toolingStatus('bun'));
    }

    public function test_clear_tooling_removes_only_the_named_tool(): void
    {
        $iuser = IsolatedUser::factory()->create([
            'server_id' => $this->server->id,
            'username' => 'alpha',
        ]);

        $iuser->setToolingVersion('node', '22');
        $iuser->setToolingVersion('bun', '1.2');

        $iuser->clearTooling('node');

        $fresh = $iuser->fresh();
        $this->assertNull($fresh->toolingVersion('node'));
        $this->assertSame('1.2', $fresh->toolingVersion('bun'));
    }

    public function test_lock_uses_legacy_key_shape_for_cross_deploy_compat(): void
    {
        $iuser = IsolatedUser::factory()->create([
            'server_id' => $this->server->id,
            'username' => 'alpha',
        ]);

        $lock = $iuser->lock();
        $this->assertTrue($lock->get(), 'iuser lock should be acquirable initially');

        // Same logical lock as the legacy `Server::isolatedUserLock($username)`.
        $legacyClash = Cache::lock("isolate:{$this->server->id}:alpha", 60);
        $this->assertFalse($legacyClash->get(), 'legacy-shaped lock should clash with the iuser lock');

        $lock->release();
    }
}
