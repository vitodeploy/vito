<?php

namespace Tests\Feature;

use App\Enums\LoadBalancerMethod;
use App\Enums\SiteStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use App\Models\SourceControl;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
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
            'aliases' => $this->castAsJson($inputs['aliases'] ?? []),
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

    #[DataProvider('create_failure_data')]
    public function test_create_site_failed_due_to_source_control(int $status): void
    {
        $inputs = [
            'type' => Laravel::id(),
            'domain' => 'example.com',
            'aliases' => ['www.example.com'],
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

    public function test_update_aliases(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('site-settings.update-aliases', [
            'server' => $this->server->id,
            'site' => $this->site,
        ]), [
            'aliases' => ['www.example.com', 'test.example.com'],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();
        $this->assertEquals(['www.example.com', 'test.example.com'], $this->site->aliases);
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
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'a',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'root',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'vito',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => '123',
                ],
            ],
            [
                [
                    'type' => PHPBlank::id(),
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'qwertyuiopasdfghjklzxcvbnmqwertyu',
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
                    'aliases' => ['www.example.com', 'www2.example.com'],
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
                    'aliases' => ['www.example.com'],
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
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => PHPMyAdmin::id(),
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'version' => '5.1.2',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => LoadBalancer::id(),
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'user' => 'example',
                    'method' => LoadBalancerMethod::ROUND_ROBIN->value,
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
