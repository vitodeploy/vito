<?php

namespace Tests\Unit;

use App\Helpers\SiteShellEnvironment;
use App\Models\Site;
use App\SiteTypes\Laravel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteShellEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_collect_returns_empty_when_no_tools_are_installed(): void
    {
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'isolated-empty',
            'path' => '/home/isolated-empty/site.test',
            'type' => Laravel::id(),
            'type_data' => [],
        ]);

        $this->assertSame([], SiteShellEnvironment::collect($site));
    }

    public function test_collect_returns_mise_shims_on_path_when_a_tool_is_installed(): void
    {
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'isolated-node',
            'path' => '/home/isolated-node/site.test',
            'type' => Laravel::id(),
            'type_data' => [],
        ]);
        $site->isolatedUser->setToolingVersion('node', '22');

        $env = SiteShellEnvironment::collect($site->refresh());

        $this->assertArrayHasKey('PATH', $env);
        $this->assertStringStartsWith('/home/isolated-node/.local/share/mise/shims:', $env['PATH']);
        $this->assertStringContainsString('/usr/local/bin', $env['PATH']);
        $this->assertStringContainsString('/home/isolated-node/.local/bin', $env['PATH']);
    }

    public function test_collect_dedupes_path_entries_from_multiple_mise_tools(): void
    {
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

        $this->assertArrayHasKey('PATH', $env);
        $occurrences = substr_count($env['PATH'], '/home/isolated-multi/.local/share/mise/shims');
        $this->assertSame(1, $occurrences, 'Mise shims path should appear exactly once.');
    }

    public function test_collect_returns_empty_when_site_user_is_empty(): void
    {
        $site = new Site(['user' => '']);

        $this->assertSame([], SiteShellEnvironment::collect($site));
    }

    public function test_wrap_builds_bash_invocation_with_env_and_optional_cd(): void
    {
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
        $this->assertStringStartsWith("bash -c '", $withoutCd);
        $this->assertStringContainsString('export PATH=', $withoutCd);
        $this->assertStringContainsString('node -v', $withoutCd);
        $this->assertStringNotContainsString('cd ', $withoutCd);

        $withCd = SiteShellEnvironment::wrap($site, 'npm install', true);
        $this->assertStringContainsString('cd ', $withCd);
        $this->assertStringContainsString('isolated-wrap/site.test', $withCd);
        $this->assertStringContainsString('npm install', $withCd);
    }
}
