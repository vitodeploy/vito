<?php

namespace Tests\Feature\SiteSettings;

use App\Services\Webserver\Apache;
use App\Services\Webserver\Caddy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VhostTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_template_matches_nginx_webserver(): void
    {
        $this->actingAs($this->user);

        $this->get(route('site-settings.vhost-template', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]))
            ->assertOk()
            ->assertJson(fn ($json) => $json->where('template', fn (string $t) => str_contains($t, 'server {') && str_contains($t, 'fastcgi_pass')));
    }

    public function test_default_template_matches_apache_webserver(): void
    {
        $this->server->webserver()?->update(['name' => Apache::id()]);

        $this->actingAs($this->user);

        $this->get(route('site-settings.vhost-template', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]))
            ->assertOk()
            ->assertJson(fn ($json) => $json->where('template', fn (string $t) => str_contains($t, '<VirtualHost') && str_contains($t, 'SetHandler')));
    }

    public function test_default_template_matches_caddy_webserver(): void
    {
        $this->server->webserver()?->update(['name' => Caddy::id()]);

        $this->actingAs($this->user);

        $this->get(route('site-settings.vhost-template', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]))
            ->assertOk()
            ->assertJson(fn ($json) => $json->where('template', fn (string $t) => str_contains($t, 'php_fastcgi') || str_contains($t, 'reverse_proxy') || str_contains($t, 'root *')));
    }
}
