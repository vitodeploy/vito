<?php

namespace Tests\Unit;

use App\Models\Site;
use App\SiteTypes\BunSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BunSiteTest extends TestCase
{
    use RefreshDatabase;

    protected Site $bunSite;

    protected BunSite $siteType;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_id(): void
    {
        $this->assertEquals('bun', BunSite::id());
    }

    public function test_language(): void
    {
        $this->assertEquals('bun', $this->siteType->language());
    }

    public function test_required_services(): void
    {
        $this->assertEquals(['webserver', 'process_manager'], $this->siteType->requiredServices());
    }

    public function test_create_time_tools_returns_bun(): void
    {
        $this->assertSame(['bun'], BunSite::createTimeTools());
    }

    public function test_deploy_commands(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'deployCommands');

        $this->assertSame(
            ['bun install --frozen-lockfile', 'bun run build'],
            $reflection->invoke($this->siteType),
        );
    }

    public function test_start_command_returns_default(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'startCommand');

        $this->assertEquals('bun run start', $reflection->invoke($this->siteType));
    }

    public function test_start_command_from_type_data(): void
    {
        $reflection = new \ReflectionMethod($this->siteType, 'startCommand');

        $this->assertEquals('bun run start', $reflection->invoke($this->siteType));
    }

    public function test_start_command_defaults(): void
    {
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'testuser',
            'path' => '/home/testuser/example.com',
            'type' => BunSite::id(),
            'type_data' => [],
        ]);
        $siteType = new BunSite($site);
        $reflection = new \ReflectionMethod($siteType, 'startCommand');

        $this->assertEquals('bun run start', $reflection->invoke($siteType));
    }

    public function test_data_with_defaults(): void
    {
        $data = $this->siteType->data([]);

        $this->assertEquals([
            'bun_version' => '1.2',
            'start_command' => 'bun run start',
        ], $data);
    }

    public function test_data_with_custom_commands(): void
    {
        $data = $this->siteType->data([
            'bun_version' => '1.1',
            'start_command' => 'bun run start:prod',
        ]);

        $this->assertEquals([
            'bun_version' => '1.1',
            'start_command' => 'bun run start:prod',
        ], $data);
    }

    public function test_create_fields(): void
    {
        $fields = $this->siteType->createFields([
            'source_control' => '1',
            'repository' => 'org/repo',
            'branch' => 'main',
            'port' => '3000',
        ]);

        $this->assertEquals([
            'source_control_id' => '1',
            'repository' => 'org/repo',
            'branch' => 'main',
            'port' => '3000',
        ], $fields);
    }

    public function test_create_rules_contain_bun_version(): void
    {
        $rules = $this->siteType->createRules([]);

        $this->assertArrayHasKey('bun_version', $rules);
        $this->assertArrayHasKey('source_control', $rules);
        $this->assertArrayHasKey('repository', $rules);
        $this->assertArrayHasKey('branch', $rules);
        $this->assertArrayHasKey('port', $rules);
        $this->assertArrayNotHasKey('build_command', $rules);
        $this->assertArrayHasKey('start_command', $rules);
        $this->assertArrayNotHasKey('package_manager', $rules);
    }

    public function test_base_commands_returns_empty_array(): void
    {
        $this->assertEquals([], $this->siteType->baseCommands());
    }

    public function test_default_deployment_script_contains_git_pull_then_deploy_commands(): void
    {
        $script = $this->siteType->defaultDeploymentScript();

        $this->assertSame(
            "git pull origin \$BRANCH\n\nbun install --frozen-lockfile\n\nbun run build\n",
            $script,
        );
    }
}
