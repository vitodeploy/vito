<?php

use App\Facades\SSH;
use App\Http\Resources\SiteResource;
use App\Models\HostedDomain;
use App\Models\User;
use App\Services\Webserver\Caddy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('endpoint persists php settings', function () {
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

    expect($this->site->type_data['php'])->toBe([
        'max_upload_size' => 64,
        'max_execution_time' => 120,
        'memory_limit' => 256,
        'max_input_vars' => 5000,
    ]);
});

test('blank values persist as null', function () {
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

    expect($this->site->type_data['php']['max_upload_size'])->toBeNull();
    expect($this->site->type_data['php']['memory_limit'])->toBeNull();
});

test('nginx vhost includes php directives intact', function () {
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
});

test('nginx vhost omits directives when unset', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringNotContainsString('PHP_VALUE', $vhost);
    $this->assertStringNotContainsString('client_max_body_size', $vhost);
    $this->assertStringNotContainsString('fastcgi_read_timeout', $vhost);
});

test('nginx vhost emits only set directives', function () {
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
});

test('caddy vhost includes php directives', function () {
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
});

test('custom template sentinel is stripped', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $this->site->type_data = ['php' => ['max_upload_size' => 64]];
    $this->site->vhost_template = "server {\n    fastcgi_param PHP_VALUE \"@@VITO_PHP_VALUE@@\";\n}";
    $this->site->save();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringNotContainsString('@@VITO_PHP_VALUE@@', $vhost);
});

test('validation requires memory limit at least upload size', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-php-settings', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'max_upload_size' => 512,
        'memory_limit' => 256,
    ])->assertSessionHasErrors('memory_limit');
});

test('validation rejects out of range values', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-php-settings', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'max_input_vars' => 5,
    ])->assertSessionHasErrors('max_input_vars');
});

test('route 404s for custom vhost template', function () {
    $this->site->vhost_template = 'server { }';
    $this->site->save();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-php-settings', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'max_upload_size' => 64,
    ])->assertNotFound();
});

test('route 404s for octane site', function () {
    $this->site->type_data = ['octane' => true];
    $this->site->save();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-php-settings', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'max_upload_size' => 64,
    ])->assertNotFound();
});

test('route 404s when vhost generation disabled', function () {
    $this->site->vhost_generation_enabled = false;
    $this->site->save();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-php-settings', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'max_upload_size' => 64,
    ])->assertNotFound();
});

test('resource exposes php settings', function () {
    $this->site->type_data = ['php' => ['max_upload_size' => 64, 'max_execution_time' => null, 'memory_limit' => null, 'max_input_vars' => null]];
    $this->site->save();
    $this->site->load('server');

    $resource = SiteResource::make($this->site)->toArray(request());

    expect($resource['supports_php_settings'])->toBeTrue();
    expect($resource['php_settings']['max_upload_size'])->toBe(64);
    expect($resource['php_settings']['memory_limit'])->toBeNull();
    $this->assertArrayNotHasKey('php', $resource['type_data']);
});

test('authorization requires project access', function () {
    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $this->actingAs($otherUser);

    $this->patch(route('site-settings.update-php-settings', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'max_upload_size' => 64,
    ])->assertForbidden();
});
