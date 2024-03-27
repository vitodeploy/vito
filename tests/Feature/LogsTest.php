<?php

namespace Tests\Feature;

use App\Models\ServerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_logs()
    {
        $this->actingAs($this->user);

        /** @var ServerLog $log */
        $log = ServerLog::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('servers.logs', $this->server))
            ->assertSuccessful()
            ->assertSeeText($log->type);
    }
}
