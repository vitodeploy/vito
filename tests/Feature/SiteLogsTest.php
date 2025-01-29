<?php

namespace Tests\Feature;

use App\Models\ServerLog;
use App\Web\Pages\Servers\Sites\Pages\Logs\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * This uses the server logs.
 * All scenarios are covered in \Tests\Feature\LogsTest
 */
class SiteLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_logs()
    {
        $this->actingAs($this->user);

        /** @var ServerLog $lastLog */
        $lastLog = ServerLog::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->get(
            Index::getUrl([
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee($lastLog->name);
    }
}
