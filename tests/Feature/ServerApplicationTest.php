<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reverse_proxy(): void
    {
        SSH::fake('OK');

        $this->actingAs($this->user);

        $this->post(route('applications.store', ['server' => $this->server->id]), [
            'type' => 'reverse-proxy',
            'domain' => 'proxy.example.com',
            'aliases' => [],
            'host' => 'localhost',
            'port' => '3000',
            'scheme' => 'http',
            'websocket' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('applications', [
            'server_id' => $this->server->id,
            'type' => 'reverse-proxy',
            'domain' => 'proxy.example.com',
        ]);
    }

    public function test_create_reverse_proxy_validation(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('applications.store', ['server' => $this->server->id]), [
            'type' => 'reverse-proxy',
            'domain' => '',
            'host' => '',
            'port' => '',
            'scheme' => 'invalid',
        ])->assertSessionHasErrors(['domain', 'host', 'port', 'scheme']);
    }

    public function test_create_reverse_proxy_duplicate_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Application::factory()->create([
            'server_id' => $this->server->id,
            'domain' => 'proxy.example.com',
        ]);

        $this->post(route('applications.store', ['server' => $this->server->id]), [
            'type' => 'reverse-proxy',
            'domain' => 'proxy.example.com',
            'host' => 'localhost',
            'port' => '3000',
            'scheme' => 'http',
        ])->assertSessionHasErrors(['domain']);
    }

    public function test_create_reverse_proxy_domain_conflicts_with_site(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('applications.store', ['server' => $this->server->id]), [
            'type' => 'reverse-proxy',
            'domain' => $this->site->domain,
            'host' => 'localhost',
            'port' => '3000',
            'scheme' => 'http',
        ])->assertSessionHasErrors(['domain']);
    }

    public function test_show_reverse_proxy(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('applications.show', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertOk();
    }

    public function test_update_reverse_proxy(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->put(route('applications.update', ['server' => $this->server->id, 'application' => $application->id]), [
            'host' => '127.0.0.1',
            'port' => '8080',
            'scheme' => 'https',
            'websocket' => true,
        ])->assertSessionDoesntHaveErrors();

        $application->refresh();
        $this->assertEquals('127.0.0.1', $application->type_data['host']);
        $this->assertEquals(8080, $application->type_data['port']);
        $this->assertEquals('https', $application->type_data['scheme']);
        $this->assertTrue($application->type_data['websocket']);
    }

    public function test_delete_application(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('applications.destroy', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('applications', [
            'id' => $application->id,
        ]);
    }

    public function test_update_custom_template(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->put(route('applications.template.update', ['server' => $this->server->id, 'application' => $application->id]), [
            'template' => 'server { listen 80; }',
        ])->assertSessionDoesntHaveErrors();

        $application->refresh();
        $this->assertEquals('server { listen 80; }', $application->custom_template);
    }

    public function test_reset_template(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
            'custom_template' => 'server { listen 80; }',
        ]);

        $this->post(route('applications.template.reset', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertSessionDoesntHaveErrors();

        $application->refresh();
        $this->assertNull($application->custom_template);
    }

    public function test_websocket_toggle_affects_config(): void
    {
        SSH::fake();

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
            'type_data' => [
                'host' => 'localhost',
                'port' => 3000,
                'scheme' => 'http',
                'websocket' => true,
            ],
        ]);

        $handler = $application->type();
        $vhost = (string) $handler->vhost('nginx');

        $this->assertStringContainsString('Upgrade', $vhost);

        $application->type_data = [
            'host' => 'localhost',
            'port' => 3000,
            'scheme' => 'http',
            'websocket' => false,
        ];
        $application->save();

        $handler = $application->type();
        $vhost = (string) $handler->vhost('nginx');

        $this->assertStringNotContainsString('Upgrade $http_upgrade', $vhost);
    }

    public function test_force_ssl_toggle(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->post(route('application-ssls.enable-force-ssl', [
            'server' => $this->server->id,
            'application' => $application->id,
        ]))->assertSessionDoesntHaveErrors();

        $application->refresh();
        $this->assertTrue($application->force_ssl);

        $this->post(route('application-ssls.disable-force-ssl', [
            'server' => $this->server->id,
            'application' => $application->id,
        ]))->assertSessionDoesntHaveErrors();

        $application->refresh();
        $this->assertFalse($application->force_ssl);
    }

    public function test_index_page(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('applications', ['server' => $this->server->id]))
            ->assertOk();
    }

    public function test_ssl_page(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('applications.ssl', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertOk();
    }

    public function test_logs_page(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('applications.logs', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertOk();
    }

    public function test_settings_page(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('applications.settings', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertOk();
    }

    public function test_deploy(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->post(route('applications.deploy', ['server' => $this->server->id, 'application' => $application->id]))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_api_index(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->getJson(route('api.projects.servers.applications', [
            'project' => $this->user->current_project_id,
            'server' => $this->server->id,
        ]))->assertOk();
    }

    public function test_api_create(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->postJson(route('api.projects.servers.applications.create', [
            'project' => $this->user->current_project_id,
            'server' => $this->server->id,
        ]), [
            'type' => 'reverse-proxy',
            'domain' => 'api-proxy.example.com',
            'host' => 'localhost',
            'port' => '4000',
            'scheme' => 'http',
        ])->assertCreated();

        $this->assertDatabaseHas('applications', [
            'domain' => 'api-proxy.example.com',
        ]);
    }

    public function test_api_show(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->getJson(route('api.projects.servers.applications.show', [
            'project' => $this->user->current_project_id,
            'server' => $this->server->id,
            'application' => $application->id,
        ]))->assertOk();
    }

    public function test_api_delete(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $application = Application::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->deleteJson(route('api.projects.servers.applications.delete', [
            'project' => $this->user->current_project_id,
            'server' => $this->server->id,
            'application' => $application->id,
        ]))->assertNoContent();
    }
}
