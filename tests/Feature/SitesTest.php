<?php

namespace Tests\Feature;

use App\Enums\LoadBalancerMethod;
use App\Enums\ServiceStatus;
use App\Enums\SiteStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Service;
use App\Models\Site;
use App\Models\SourceControl;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SiteTypes\MiseBun;
use App\SiteTypes\MiseNodeJS;
use App\SiteTypes\NodeJS;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\PHPMyAdmin;
use App\SiteTypes\Wordpress;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SitesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $inputs
     */
    #[DataProvider('create_data')]
    public function test_create_site(array $inputs): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        if (isset($inputs['database']) && isset($inputs['database_user'])) {
            /** @var Database $database */
            $database = Database::factory()->create([
                'server_id' => $this->server->id,
            ]);
            /** @var DatabaseUser $databaseUser */
            $databaseUser = DatabaseUser::factory()->create([
                'server_id' => $this->server->id,
            ]);
            $inputs['database'] = $database->id;
            $inputs['database_user'] = $databaseUser->id;
        }

        $this->actingAs($this->user);
        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
        ]);

        $inputs['source_control'] = $sourceControl->id;

        $this->post(route('sites.store', ['server' => $this->server]), $inputs)
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sites', [
            'domain' => $inputs['domain'],
            'status' => SiteStatus::READY->value,
            'user' => $inputs['user'],
            'path' => '/home/'.$inputs['user'].'/'.$inputs['domain'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    #[DataProvider('failure_create_data')]
    public function test_isolated_user_failure(array $inputs): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), $inputs)
            ->assertSessionHasErrors();
    }

    public function test_create_site_reusing_existing_isolated_user(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'first.example.com',
            'path' => '/home/shared/first.example.com',
            'php_version' => '8.2',
        ]);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'second.example.com',
            'aliases' => [],
            'php_version' => '8.2',
            'web_directory' => 'public',
            'user' => 'shared',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sites', [
            'domain' => 'first.example.com',
            'user' => 'shared',
        ]);
        $this->assertDatabaseHas('sites', [
            'domain' => 'second.example.com',
            'user' => 'shared',
            'path' => '/home/shared/second.example.com',
        ]);

        SSH::assertNotExecutedContains('useradd');
    }

    public function test_isolated_users_endpoint_lists_users_with_counts(): void
    {
        $this->actingAs($this->user);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shop',
            'domain' => 'shop1.test',
            'path' => '/home/shop/shop1.test',
        ]);
        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shop',
            'domain' => 'shop2.test',
            'path' => '/home/shop/shop2.test',
        ]);
        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'blog',
            'domain' => 'blog.test',
            'path' => '/home/blog/blog.test',
        ]);

        $response = $this->getJson(route('sites.isolated-users', ['server' => $this->server]));

        $response->assertSuccessful();
        $data = collect($response->json())->keyBy('user');

        $this->assertSame(2, $data['shop']['sites_count']);
        $this->assertSame(1, $data['blog']['sites_count']);
        $this->assertArrayNotHasKey('vito', $data->all());
    }

    public function test_delete_site_keeps_isolated_user_when_others_share_it(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $siteA = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'a.test',
            'path' => '/home/shared/a.test',
            'php_version' => '8.2',
        ]);
        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'b.test',
            'path' => '/home/shared/b.test',
            'php_version' => '8.2',
        ]);

        $this->delete(route('site-settings.destroy', [
            'server' => $this->server->id,
            'site' => $siteA->id,
        ]), [
            'domain' => $siteA->domain,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('sites', ['id' => $siteA->id]);
        $this->assertDatabaseHas('sites', ['domain' => 'b.test', 'user' => 'shared']);

        SSH::assertNotExecutedContains('userdel');
    }

    public function test_delete_last_isolated_site_removes_user(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'lonely',
            'domain' => 'lonely.test',
            'path' => '/home/lonely/lonely.test',
            'php_version' => '8.2',
        ]);

        $this->delete(route('site-settings.destroy', [
            'server' => $this->server->id,
            'site' => $site->id,
        ]), [
            'domain' => $site->domain,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);

        SSH::assertExecutedContains('userdel');
    }

    public function test_php_version_switch_removes_old_pool_when_not_shared(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Service::query()->create([
            'server_id' => $this->server->id,
            'type' => 'php',
            'name' => 'php',
            'version' => '8.4',
            'status' => ServiceStatus::READY,
        ]);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'a.test',
            'path' => '/home/shared/a.test',
            'php_version' => '8.2',
        ]);
        $siteB = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'b.test',
            'path' => '/home/shared/b.test',
            'php_version' => '8.4',
        ]);

        $this->patch(route('site-settings.update-php-version', [
            'server' => $this->server->id,
            'site' => $siteB->id,
        ]), [
            'version' => '8.2',
        ])->assertSessionDoesntHaveErrors();

        $siteB->refresh();
        $this->assertSame('8.2', $siteB->php_version);

        SSH::assertExecutedContains('rm -f /etc/php/8.4/fpm/pool.d/shared.conf');
    }

    public function test_php_version_switch_preserves_shared_old_pool(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Service::query()->create([
            'server_id' => $this->server->id,
            'type' => 'php',
            'name' => 'php',
            'version' => '8.4',
            'status' => ServiceStatus::READY,
        ]);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'a.test',
            'path' => '/home/shared/a.test',
            'php_version' => '8.2',
        ]);
        $siteB = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'shared',
            'domain' => 'b.test',
            'path' => '/home/shared/b.test',
            'php_version' => '8.2',
        ]);

        $this->patch(route('site-settings.update-php-version', [
            'server' => $this->server->id,
            'site' => $siteB->id,
        ]), [
            'version' => '8.4',
        ])->assertSessionDoesntHaveErrors();

        $siteB->refresh();
        $this->assertSame('8.4', $siteB->php_version);

        SSH::assertNotExecutedContains('rm -f /etc/php/8.2/fpm/pool.d/shared.conf');
    }

    #[DataProvider('create_failure_data')]
    public function test_create_site_failed_due_to_source_control(int $status): void
    {
        $inputs = [
            'type' => Laravel::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => 'public',
            'repository' => 'test/test',
            'branch' => 'main',
            'composer' => true,
            'user' => 'example',
        ];

        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], $status),
        ]);

        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
        ]);

        $inputs['source_control'] = $sourceControl->id;

        $this->post(route('sites.store', ['server' => $this->server]), $inputs)
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('sites', [
            'domain' => 'example.com',
            'status' => SiteStatus::READY,
        ]);
    }

    public function test_see_sites_list(): void
    {
        $this->actingAs($this->user);

        Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('sites', [
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('sites/index'));
    }

    public function test_delete_site(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('site-settings.destroy', [
            'server' => $this->server->id,
            'site' => $site->id,
        ]), [
            'domain' => $site->domain,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('sites', [
            'id' => $site->id,
        ]);
    }

    public function test_change_php_version(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('site-settings.update-php-version', [
            'server' => $this->server->id,
            'site' => $site->id,
        ]), [
            'version' => '8.2',
        ])
            ->assertSessionDoesntHaveErrors();

        $site->refresh();

        $this->assertEquals('8.2', $site->php_version);
    }

    public function test_update_source_control(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
        ]);

        $this->patch(route('site-settings.update-source-control', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'source_control' => $sourceControl->id,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertEquals($sourceControl->id, $this->site->source_control_id);
    }

    public function test_failed_to_update_source_control(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 404),
        ]);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
        ]);

        $this->patch(route('site-settings.update-source-control', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'source_control' => $sourceControl->id,
        ])
            ->assertSessionHasErrors();
    }

    public function test_update_v_host(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->patch(route('site-settings.update-vhost', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'vhost' => 'test',
        ])
            ->assertSessionDoesntHaveErrors();
    }

    public function test_see_logs(): void
    {
        $this->actingAs($this->user);

        $this->get(route('sites.logs', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('sites/logs'));
    }

    public function test_change_branch(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-branch', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'branch' => 'master',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();
        $this->assertEquals('master', $this->site->branch);

        SSH::assertExecutedContains('git checkout -f master');
    }

    public function test_update_web_directory(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-web-directory', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'web_directory' => 'public',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();
        $this->assertEquals('public', $this->site->web_directory);
    }

    public function test_update_web_directory_empty(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-web-directory', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'web_directory' => '',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();
        $this->assertNull($this->site->web_directory);
    }

    public function test_update_web_directory_normalizes_slashes(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-web-directory', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'web_directory' => '/public/dist/',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();
        $this->assertEquals('public/dist', $this->site->web_directory);
    }

    public function test_update_web_directory_normalizes_root(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-web-directory', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'web_directory' => '/',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();
        $this->assertNull($this->site->web_directory);
    }

    public function test_update_web_directory_rejects_invalid_characters(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-web-directory', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'web_directory' => 'public@invalid!',
        ])
            ->assertSessionHasErrors(['web_directory']);
    }

    public function test_update_web_directory_rejects_directory_traversal(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-web-directory', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'web_directory' => '../etc/passwd',
        ])
            ->assertSessionHasErrors(['web_directory']);
    }

    public function test_create_site_with_valid_web_directory(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => 'public/dist',
            'user' => 'example',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sites', [
            'domain' => 'example.com',
            'web_directory' => 'public/dist',
        ]);
    }

    public function test_create_site_with_special_characters_web_directory(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => 'public-dist_v1.0',
            'user' => 'example',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sites', [
            'domain' => 'example.com',
            'web_directory' => 'public-dist_v1.0',
        ]);
    }

    public function test_create_site_normalizes_web_directory_slashes(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => '/public/',
            'user' => 'example',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sites', [
            'domain' => 'example.com',
            'web_directory' => 'public',
        ]);
    }

    public function test_create_site_normalizes_root_web_directory(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => '/',
            'user' => 'example',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sites', [
            'domain' => 'example.com',
            'web_directory' => null,
        ]);
    }

    public function test_create_site_rejects_invalid_web_directory_characters(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => 'public@invalid!',
            'user' => 'example',
        ])
            ->assertSessionHasErrors(['web_directory']);

        $this->assertDatabaseMissing('sites', [
            'domain' => 'example.com',
        ]);
    }

    public function test_create_site_rejects_directory_traversal(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => PHPBlank::id(),
            'domain' => 'example.com',
            'php_version' => '8.2',
            'web_directory' => '../etc/passwd',
            'user' => 'example',
        ])
            ->assertSessionHasErrors(['web_directory']);

        $this->assertDatabaseMissing('sites', [
            'domain' => 'example.com',
        ]);
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function failure_create_data(): array
    {
        return [
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'a',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'root',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'vito',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => '123',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'qwertyuiopasdfghjklzxcvbnmqwertyu',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'www-data',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'mysql',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'ubuntu',
                ],
            ],
        ];
    }

    /**
     * @return array<array<array<string, mixed>>>
     */
    public static function create_data(): array
    {
        return [
            [
                [
                    'type' => Laravel::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'composer' => true,
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => Wordpress::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'title' => 'Example',
                    'username' => 'example',
                    'email' => 'email@example.com',
                    'password' => 'password',
                    'database' => '1',
                    'database_user' => '1',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => PHPMyAdmin::id(),
                    'domain' => 'example.com',
                    'php_version' => '8.2',
                    'version' => '5.1.2',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => LoadBalancer::id(),
                    'domain' => 'example.com',
                    'user' => 'example',
                    'method' => LoadBalancerMethod::ROUND_ROBIN->value,
                ],
            ],
            [
                [
                    'type' => MiseNodeJS::id(),
                    'domain' => 'example.com',
                    'node_version' => '20',
                    'package_manager' => 'npm',
                    'port' => '3000',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => MiseNodeJS::id(),
                    'domain' => 'example.com',
                    'node_version' => '22',
                    'package_manager' => 'yarn',
                    'port' => '3000',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => MiseNodeJS::id(),
                    'domain' => 'example.com',
                    'node_version' => '22',
                    'package_manager' => 'pnpm',
                    'port' => '3000',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'build_command' => 'pnpm run build:prod',
                    'start_command' => 'pnpm run start:prod',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => NodeJS::id(),
                    'domain' => 'example.com',
                    'port' => '3000',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => MiseBun::id(),
                    'domain' => 'example.com',
                    'bun_version' => '1.2',
                    'port' => '3000',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => MiseBun::id(),
                    'domain' => 'example.com',
                    'bun_version' => '1.1',
                    'port' => '3000',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'build_command' => 'bun run build:prod',
                    'start_command' => 'bun run start:prod',
                    'user' => 'example',
                ],
            ],
        ];
    }

    /**
     * @return array<array<int>>
     */
    public static function create_failure_data(): array
    {
        return [
            [401],
            [403],
            [404],
        ];
    }
}
