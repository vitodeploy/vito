<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\SiteTypes\Laravel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModernDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_modern_deployment(): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        $this->site->update([
            'type' => Laravel::id(),
        ]);

        $this->actingAs($this->user)
            ->post(route('site-features.action', [
                'server' => $this->server,
                'site' => $this->site,
                'feature' => 'modern-deployment',
                'action' => 'enable',
            ]), [
                'shared_resources' => '.env',
                'history' => 10,
            ])
            ->assertRedirect();

        $this->site->refresh();

        $this->assertTrue($this->site->type_data['modern_deployment']);
        $this->assertEquals('10', $this->site->type_data['modern_deployment_history']);
        $this->assertEquals(['.env'], $this->site->type_data['modern_deployment_shared_resources']);
    }

    public function test_disable_modern_deployment(): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        $this->site->update([
            'type' => Laravel::id(),
            'type_data' => [
                'modern_deployment' => true,
                'modern_deployment_history' => 10,
                'modern_deployment_shared_resources' => ['.env'],
            ],
        ]);

        $this->actingAs($this->user)
            ->post(route('site-features.action', [
                'server' => $this->server,
                'site' => $this->site,
                'feature' => 'modern-deployment',
                'action' => 'disable',
            ]))
            ->assertRedirect();

        $this->site->refresh();

        $this->assertFalse($this->site->type_data['modern_deployment'] ?? false);
        $this->assertArrayNotHasKey('modern_deployment_history', $this->site->type_data);
        $this->assertArrayNotHasKey('modern_deployment_shared_resources', $this->site->type_data);
    }

    public function test_configure_modern_deployment(): void
    {
        $this->site->update([
            'type' => Laravel::id(),
            'type_data' => [
                'modern_deployment' => true,
                'modern_deployment_history' => 10,
                'modern_deployment_shared_resources' => ['.env'],
            ],
        ]);

        $this->actingAs($this->user)
            ->post(route('site-features.action', [
                'server' => $this->server,
                'site' => $this->site,
                'feature' => 'modern-deployment',
                'action' => 'configuration',
            ]), [
                'shared_resources' => '.env,.env.local',
                'history' => 5,
            ])
            ->assertRedirect();

        $this->site->refresh();

        $this->assertTrue($this->site->type_data['modern_deployment']);
        $this->assertEquals('5', $this->site->type_data['modern_deployment_history']);
        $this->assertEquals(['.env', '.env.local'], $this->site->type_data['modern_deployment_shared_resources']);
    }
}
