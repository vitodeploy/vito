<?php

namespace Tests\Feature\SiteSettings;

use App\Facades\SSH;
use App\Http\Resources\SiteResource;
use App\Models\HostedDomain;
use App\Models\User;
use App\Services\Webserver\Caddy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_persists_php_settings(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => 64,
            'max_execution_time' => 120,
            'memory_limit' => 256,
            'max_input_vars' => 5000,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertSame([
            'max_upload_size' => 64,
            'max_execution_time' => 120,
            'memory_limit' => 256,
            'max_input_vars' => 5000,
        ], $this->site->type_data['php']);
    }

    public function test_blank_values_persist_as_null(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => '',
            'max_execution_time' => null,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertNull($this->site->type_data['php']['max_upload_size']);
        $this->assertNull($this->site->type_data['php']['memory_limit']);
    }

    public function test_nginx_vhost_includes_php_directives_intact(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->type_data = [
            'php' => [
                'max_upload_size' => 64,
                'max_execution_time' => 120,
                'memory_limit' => 256,
                'max_input_vars' => 5000,
            ],
        ];
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('fastcgi_param PHP_VALUE "upload_max_filesize=64M', $vhost);
        $this->assertStringContainsString("\npost_max_size=64M", $vhost);
        $this->assertStringContainsString("\nmemory_limit=256M", $vhost);
        $this->assertStringContainsString('max_input_vars=5000";', $vhost);

        $this->assertStringContainsString('client_max_body_size 64M;', $vhost);
        $this->assertStringContainsString('fastcgi_read_timeout 120s;', $vhost);
    }

    public function test_nginx_vhost_omits_directives_when_unset(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringNotContainsString('PHP_VALUE', $vhost);
        $this->assertStringNotContainsString('client_max_body_size', $vhost);
        $this->assertStringNotContainsString('fastcgi_read_timeout', $vhost);
    }

    public function test_nginx_vhost_emits_only_set_directives(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->type_data = ['php' => ['max_input_vars' => 5000]];
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('fastcgi_param PHP_VALUE "max_input_vars=5000";', $vhost);
        $this->assertStringNotContainsString('client_max_body_size', $vhost);
        $this->assertStringNotContainsString('fastcgi_read_timeout', $vhost);
    }

    public function test_caddy_vhost_includes_php_directives(): void
    {
        $this->server->webserver()?->update(['name' => Caddy::id()]);

        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->type_data = [
            'php' => [
                'max_upload_size' => 64,
                'max_execution_time' => 120,
                'memory_limit' => 256,
                'max_input_vars' => 5000,
            ],
        ];
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('env PHP_VALUE "upload_max_filesize=64M', $vhost);
        $this->assertStringContainsString('max_size 64MB', $vhost);
    }

    public function test_custom_template_sentinel_is_stripped(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->type_data = ['php' => ['max_upload_size' => 64]];
        $this->site->vhost_template = "server {\n    fastcgi_param PHP_VALUE \"@@VITO_PHP_VALUE@@\";\n}";
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringNotContainsString('@@VITO_PHP_VALUE@@', $vhost);
    }

    public function test_validation_requires_memory_limit_at_least_upload_size(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => 512,
            'memory_limit' => 256,
        ])->assertSessionHasErrors('memory_limit');
    }

    public function test_validation_rejects_out_of_range_values(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_input_vars' => 5,
        ])->assertSessionHasErrors('max_input_vars');
    }

    public function test_route_404s_for_custom_vhost_template(): void
    {
        $this->site->vhost_template = 'server { }';
        $this->site->save();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => 64,
        ])->assertNotFound();
    }

    public function test_route_404s_for_octane_site(): void
    {
        $this->site->type_data = ['octane' => true];
        $this->site->save();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => 64,
        ])->assertNotFound();
    }

    public function test_route_404s_when_vhost_generation_disabled(): void
    {
        $this->site->vhost_generation_enabled = false;
        $this->site->save();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => 64,
        ])->assertNotFound();
    }

    public function test_resource_exposes_php_settings(): void
    {
        $this->site->type_data = ['php' => ['max_upload_size' => 64, 'max_execution_time' => null, 'memory_limit' => null, 'max_input_vars' => null]];
        $this->site->save();
        $this->site->load('server');

        $resource = SiteResource::make($this->site)->toArray(request());

        $this->assertTrue($resource['supports_php_settings']);
        $this->assertSame(64, $resource['php_settings']['max_upload_size']);
        $this->assertNull($resource['php_settings']['memory_limit']);
        $this->assertArrayNotHasKey('php', $resource['type_data']);
    }

    public function test_authorization_requires_project_access(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();

        $this->actingAs($otherUser);

        $this->patch(route('site-settings.update-php-settings', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'max_upload_size' => 64,
        ])->assertForbidden();
    }
}
