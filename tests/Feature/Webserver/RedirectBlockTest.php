<?php

use App\Enums\RedirectStatus;
use App\Models\HostedDomain;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nginx renders websocket upgrade headers for proxy redirect', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    Redirect::factory()->create([
        'site_id' => $this->site->id,
        'from' => '/app',
        'to' => 'https://backend.example.com',
        'mode' => 1000,
        'websocket' => true,
        'status' => RedirectStatus::READY,
    ]);

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('location /app {', $vhost);
    $this->assertStringContainsString('proxy_pass https://backend.example.com;', $vhost);
    $this->assertStringContainsString('proxy_http_version 1.1;', $vhost);
    $this->assertStringContainsString('proxy_set_header Upgrade $http_upgrade;', $vhost);
    $this->assertStringContainsString('proxy_set_header Connection "upgrade";', $vhost);
    $this->assertStringContainsString('proxy_read_timeout 60s;', $vhost);
    $this->assertStringContainsString('proxy_ssl_server_name on;', $vhost);
});

test('nginx renders plain proxy block without websocket', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    Redirect::factory()->create([
        'site_id' => $this->site->id,
        'from' => '/app',
        'to' => 'https://backend.example.com',
        'mode' => 1000,
        'websocket' => false,
        'status' => RedirectStatus::READY,
    ]);

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('location /app {', $vhost);
    $this->assertStringContainsString('proxy_set_header Host backend.example.com;', $vhost);
    $this->assertStringContainsString('proxy_ssl_server_name on;', $vhost);
    $this->assertStringNotContainsString('proxy_set_header Upgrade $http_upgrade;', $vhost);
});
