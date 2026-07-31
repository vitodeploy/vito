<?php

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Services\Webserver\Caddy;
use App\Services\Webserver\Nginx;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nginx deploy splash runs expected commands and writes default vhost', function () {
    SSH::fake();

    /** @var Nginx $nginx */
    $nginx = $this->server->webserver()->handler();

    $nginx->deploySplash();

    SSH::assertExecutedContains('sudo rm -f /etc/nginx/sites-enabled/default');
    SSH::assertExecutedContains('sudo mkdir -p /var/www/vito-splash');
    SSH::assertExecutedContains("> '/var/www/vito-splash/index.html'");
    SSH::assertExecutedContains("> '/etc/nginx/sites-available/000-default'");
    SSH::assertExecutedContains('sudo ln -sf /etc/nginx/sites-available/000-default /etc/nginx/sites-enabled/000-default');

    SSH::assertNotExecutedContains(
        'systemctl reload nginx',
        'deploySplash() must not reload nginx — install() restarts it and the reload can fail on fresh installs.'
    );

    $this->addToAssertionCount(6);
});

test('caddy deploy splash runs expected commands and writes default vhost', function () {
    $this->server->webserver()->delete();
    $this->server->services()->create([
        'type' => Caddy::type(),
        'name' => Caddy::id(),
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    SSH::fake();

    /** @var Caddy $caddy */
    $caddy = $this->server->refresh()->webserver()->handler();

    $caddy->deploySplash();

    SSH::assertExecutedContains('sudo mkdir -p /var/www/vito-splash');
    SSH::assertExecutedContains("> '/var/www/vito-splash/index.html'");
    SSH::assertExecutedContains("> '/etc/caddy/sites-enabled/000-default.caddy'");

    SSH::assertNotExecutedContains(
        'caddy reload',
        'deploySplash() must not reload caddy — install() restarts it and the reload can fail on fresh installs.'
    );

    $this->addToAssertionCount(4);
});
