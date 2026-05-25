<?php

namespace Tests\Unit;

use App\Models\Site;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NodeSiteTest extends TestCase
{
    use RefreshDatabase;

    private function siteType(string $packageManager): NodeSite
    {
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
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

    public function test_id_and_language(): void
    {
        $this->assertSame('node', NodeSite::id());
        $this->assertSame('nodejs', $this->siteType('npm')->language());
    }

    public function test_deploy_commands_for_npm(): void
    {
        $siteType = $this->siteType('npm');
        $reflection = new \ReflectionMethod($siteType, 'deployCommands');

        $this->assertSame(
            ['npm ci', 'npm run build'],
            $reflection->invoke($siteType),
        );
    }

    public function test_deploy_commands_for_pnpm(): void
    {
        $siteType = $this->siteType('pnpm');
        $reflection = new \ReflectionMethod($siteType, 'deployCommands');

        $this->assertSame(
            ['pnpm install --frozen-lockfile', 'pnpm run build'],
            $reflection->invoke($siteType),
        );
    }

    public function test_deploy_commands_for_yarn(): void
    {
        $siteType = $this->siteType('yarn');
        $reflection = new \ReflectionMethod($siteType, 'deployCommands');

        $this->assertSame(
            ['yarn install --frozen-lockfile', 'yarn build'],
            $reflection->invoke($siteType),
        );
    }

    public function test_default_deployment_script_for_pnpm(): void
    {
        $script = $this->siteType('pnpm')->defaultDeploymentScript();

        $this->assertSame(
            "git pull origin \$BRANCH\n\npnpm install --frozen-lockfile\n\npnpm run build\n",
            $script,
        );
    }
}
