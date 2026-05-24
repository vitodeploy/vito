<?php

namespace Tests\Unit\Models;

use App\Models\Site;
use App\SiteTypes\BunSite;
use App\SiteTypes\NodeJS;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SiteTypeResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_mise_bun_rows_resolve_to_bun_site(): void
    {
        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'mise_bun',
        ]);

        $this->assertInstanceOf(BunSite::class, $site->type());
    }

    public function test_legacy_mise_nodejs_rows_resolve_to_node_site(): void
    {
        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'mise_nodejs',
        ]);

        $this->assertInstanceOf(NodeSite::class, $site->type());
    }

    public function test_deprecated_nodejs_install_throws(): void
    {
        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'type' => NodeJS::id(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/deprecated/i');

        $site->type()->install();
    }
}
