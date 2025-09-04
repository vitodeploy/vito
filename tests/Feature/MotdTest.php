<?php

namespace Feature;

use App\Facades\SSH;
use App\ServerFeatures\Motd\MotdPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotdTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_motd(): void
    {
        SSH::fake();

        $this->actingAs($this->user)
            ->post(route('server-features.action', [
                'server' => $this->server,
                'feature' => 'motd',
                'action' => 'enable',
            ]), [
                'position' => MotdPosition::END,
                'content' => 'This is a test message of the day.',
            ])
            ->assertRedirect();

        $this->server->refresh();

        $this->assertTrue($this->server->feature_data['motd']);
        $this->assertEquals(MotdPosition::END, $this->server->feature_data['motd_position']);
    }

    public function test_disable_motd(): void
    {
        SSH::fake();

        $this->server->update([
            'feature_data' => [
                'motd' => true,
                'motd_position' => MotdPosition::END,
            ],
        ]);

        $this->actingAs($this->user)
            ->post(route('server-features.action', [
                'server' => $this->server,
                'feature' => 'motd',
                'action' => 'disable',
            ]))
            ->assertRedirect();

        $this->server->refresh();

        $this->assertFalse($this->server->feature_data['motd'] ?? false);
        $this->assertArrayNotHasKey('motd_position', $this->server->feature_data);
    }
}
