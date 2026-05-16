<?php

namespace Tests\Feature\SiteSettings;

use App\Actions\Site\UpdateBasicAuth;
use App\Facades\SSH;
use App\Http\Resources\SiteResource;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\User;
use App\Services\Webserver\Caddy;
use App\Services\Webserver\Nginx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_enables_basic_auth_with_users(): void
    {
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

        $this->assertTrue($auth['enabled']);
        $this->assertCount(2, $auth['users']);
        $this->assertSame('alice', $auth['users'][0]['username']);
        $this->assertStringStartsWith('$apr1$', $auth['users'][0]['apr1']);
        $this->assertTrue(password_verify('secret123', $auth['users'][0]['bcrypt']));

        SSH::assertExecutedContains('/etc/nginx/auth/site-'.$this->site->id.'.htpasswd');
    }

    public function test_empty_users_forces_disabled(): void
    {
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
        $site = \Mockery::mock($this->site)->makePartial();
        $site->shouldReceive('webserver->updateVHost')->andReturn();
        $site->shouldReceive('webserver->id')->andReturn(Nginx::id());

        SSH::fake();

        app(UpdateBasicAuth::class)->update($site, [
            'enabled' => true,
            'users' => [],
        ]);

        $site->refresh();
        $this->assertFalse($site->type_data['basic_auth']['enabled']);
        $this->assertSame([], $site->type_data['basic_auth']['users']);

        SSH::assertExecutedContains('rm -f');
    }

    public function test_blank_password_preserves_existing_hashes(): void
    {
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
        $site = \Mockery::mock($this->site)->makePartial();
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
        $this->assertSame($apr1, $site->type_data['basic_auth']['users'][0]['apr1']);
        $this->assertSame($bcrypt, $site->type_data['basic_auth']['users'][0]['bcrypt']);
    }

    public function test_validation_rejects_duplicate_usernames(): void
    {
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
    }

    public function test_validation_rejects_new_user_without_password(): void
    {
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
    }

    public function test_caddy_server_does_not_write_htpasswd_file(): void
    {
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
    }

    public function test_vhost_generation_includes_auth_basic_when_enabled(): void
    {
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
    }

    public function test_vhost_exempts_acme_challenge_path_when_basic_auth_enabled(): void
    {
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
    }

    public function test_vhost_generation_omits_auth_basic_when_disabled(): void
    {
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
    }

    public function test_site_resource_does_not_leak_hashes(): void
    {
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

        $this->assertIsString($json);
        $this->assertStringNotContainsString('apr1', $json);
        $this->assertStringNotContainsString('bcrypt', $json);
        $this->assertStringNotContainsString('leakedapr1', $json);
        $this->assertStringNotContainsString('leakedbcrypt', $json);
        $this->assertStringContainsString('alice', $json);
    }

    public function test_disable_keeps_users_but_removes_htpasswd_and_strips_auth_basic(): void
    {
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
        $this->assertFalse($this->site->type_data['basic_auth']['enabled']);
        $this->assertCount(1, $this->site->type_data['basic_auth']['users']);
        $this->assertSame($apr1, $this->site->type_data['basic_auth']['users'][0]['apr1']);
        $this->assertSame($bcrypt, $this->site->type_data['basic_auth']['users'][0]['bcrypt']);

        SSH::assertExecutedContains('rm -f');

        $vhost = $this->site->webserver()->generateVhost($this->site);
        $this->assertStringNotContainsString('auth_basic', $vhost);
    }

    public function test_authorization_requires_project_access(): void
    {
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
    }
}
