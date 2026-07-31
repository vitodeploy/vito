<?php

use App\Actions\Site\UpdateBasicAuth;
use App\Facades\SSH;
use App\Http\Resources\SiteResource;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\User;
use App\Services\Webserver\Caddy;
use App\Services\Webserver\Nginx;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('endpoint enables basic auth with users', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-basic-auth', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'enabled' => true,
        'users' => [
            ['username' => 'alice', 'password' => 'secret123'],
            ['username' => 'bob', 'password' => 'hunter2'],
        ],
    ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    $auth = $this->site->type_data['basic_auth'];

    expect($auth['enabled'])->toBeTrue();
    expect($auth['users'])->toHaveCount(2);
    expect($auth['users'][0]['username'])->toBe('alice');
    expect($auth['users'][0]['apr1'])->toStartWith('$apr1$');
    expect(password_verify('secret123', $auth['users'][0]['bcrypt']))->toBeTrue();

    SSH::assertExecutedContains('/etc/nginx/auth/site-'.$this->site->id.'.htpasswd');
});

test('empty users forces disabled', function () {
    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => true,
            'users' => [
                ['username' => 'alice', 'apr1' => '$apr1$existing', 'bcrypt' => '$2y$10$existing'],
            ],
        ],
    ];
    $this->site->save();

    /** @var Site $site */
    $site = Mockery::mock($this->site)->makePartial();
    $site->shouldReceive('webserver->updateVHost')->andReturn();
    $site->shouldReceive('webserver->id')->andReturn(Nginx::id());

    SSH::fake();

    app(UpdateBasicAuth::class)->update($site, [
        'enabled' => true,
        'users' => [],
    ]);

    $site->refresh();
    expect($site->type_data['basic_auth']['enabled'])->toBeFalse();
    expect($site->type_data['basic_auth']['users'])->toBe([]);

    SSH::assertExecutedContains('rm -f');
});

test('blank password preserves existing hashes', function () {
    $apr1 = '$apr1$abcdefgh$existinghash1234567890';
    $bcrypt = password_hash('original', PASSWORD_BCRYPT);

    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => true,
            'users' => [
                ['username' => 'alice', 'apr1' => $apr1, 'bcrypt' => $bcrypt],
            ],
        ],
    ];
    $this->site->save();

    /** @var Site $site */
    $site = Mockery::mock($this->site)->makePartial();
    $site->shouldReceive('webserver->updateVHost')->andReturn();
    $site->shouldReceive('webserver->id')->andReturn(Nginx::id());

    SSH::fake();

    app(UpdateBasicAuth::class)->update($site, [
        'enabled' => true,
        'users' => [
            ['username' => 'alice', 'password' => ''],
        ],
    ]);

    $site->refresh();
    expect($site->type_data['basic_auth']['users'][0]['apr1'])->toBe($apr1);
    expect($site->type_data['basic_auth']['users'][0]['bcrypt'])->toBe($bcrypt);
});

test('validation rejects duplicate usernames', function () {
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-basic-auth', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'enabled' => true,
        'users' => [
            ['username' => 'alice', 'password' => 'p1'],
            ['username' => 'alice', 'password' => 'p2'],
        ],
    ])->assertSessionHasErrors();
});

test('validation rejects new user without password', function () {
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-basic-auth', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'enabled' => true,
        'users' => [
            ['username' => 'newbie', 'password' => ''],
        ],
    ])->assertSessionHasErrors();
});

test('caddy server does not write htpasswd file', function () {
    $this->server->webserver()?->update([
        'name' => Caddy::id(),
    ]);

    SSH::fake();
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-basic-auth', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'enabled' => true,
        'users' => [
            ['username' => 'alice', 'password' => 'secret123'],
        ],
    ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    SSH::assertNotExecutedContains('/etc/nginx/auth/');
});

test('vhost generation includes auth basic when enabled', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => true,
            'users' => [
                ['username' => 'alice', 'apr1' => '$apr1$saltxxxx$hashhashhashhashhashhh', 'bcrypt' => '$2y$10$bcrypthashhere'],
            ],
        ],
    ];
    $this->site->save();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('auth_basic "'.$this->site->domain.'"', $vhost);
    $this->assertStringContainsString('auth_basic_user_file /etc/nginx/auth/site-'.$this->site->id.'.htpasswd', $vhost);
});

test('vhost exempts acme challenge path when basic auth enabled', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => true,
            'users' => [
                ['username' => 'alice', 'apr1' => '$apr1$saltxxxx$hashhashhashhashhashhh', 'bcrypt' => '$2y$10$bcrypthashhere'],
            ],
        ],
    ];
    $this->site->save();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('location ^~ /.well-known/acme-challenge/', $vhost);
    $this->assertStringContainsString('auth_basic off', $vhost);
});

test('vhost generation omits auth basic when disabled', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => false,
            'users' => [],
        ],
    ];
    $this->site->save();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringNotContainsString('auth_basic', $vhost);
});

test('site resource does not leak hashes', function () {
    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => true,
            'users' => [
                ['username' => 'alice', 'apr1' => '$apr1$leakedapr1', 'bcrypt' => '$2y$10$leakedbcrypt'],
            ],
        ],
    ];
    $this->site->save();
    $this->site->load('server');

    $json = json_encode(SiteResource::make($this->site)->toArray(request()));

    expect($json)->toBeString();
    $this->assertStringNotContainsString('apr1', $json);
    $this->assertStringNotContainsString('bcrypt', $json);
    $this->assertStringNotContainsString('leakedapr1', $json);
    $this->assertStringNotContainsString('leakedbcrypt', $json);
    $this->assertStringContainsString('alice', $json);
});

test('disable keeps users but removes htpasswd and strips auth basic', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $apr1 = '$apr1$abcdefgh$existinghash1234567890';
    $bcrypt = password_hash('original', PASSWORD_BCRYPT);
    $this->site->type_data = [
        'basic_auth' => [
            'enabled' => true,
            'users' => [
                ['username' => 'alice', 'apr1' => $apr1, 'bcrypt' => $bcrypt],
            ],
        ],
    ];
    $this->site->save();

    SSH::fake();
    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-basic-auth', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'enabled' => false,
        'users' => [
            ['username' => 'alice', 'password' => ''],
        ],
    ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    expect($this->site->type_data['basic_auth']['enabled'])->toBeFalse();
    expect($this->site->type_data['basic_auth']['users'])->toHaveCount(1);
    expect($this->site->type_data['basic_auth']['users'][0]['apr1'])->toBe($apr1);
    expect($this->site->type_data['basic_auth']['users'][0]['bcrypt'])->toBe($bcrypt);

    SSH::assertExecutedContains('rm -f');

    $vhost = $this->site->webserver()->generateVhost($this->site);
    $this->assertStringNotContainsString('auth_basic', $vhost);
});

test('authorization requires project access', function () {
    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $this->actingAs($otherUser);

    $this->patch(route('site-settings.update-basic-auth', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'enabled' => true,
        'users' => [
            ['username' => 'alice', 'password' => 'secret123'],
        ],
    ])->assertForbidden();
});
