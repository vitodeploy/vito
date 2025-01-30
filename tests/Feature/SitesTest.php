<?php

namespace Tests\Feature;

use App\Enums\LoadBalancerMethod;
use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Enums\SourceControl;
use App\Facades\SSH;
use App\Models\Site;
use App\Web\Pages\Servers\Sites\Index;
use App\Web\Pages\Servers\Sites\Settings;
use App\Web\Pages\Servers\Sites\View;
use App\Web\Pages\Servers\Sites\Widgets\SiteDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SitesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider create_data
     */
    public function test_create_site(array $inputs): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        $this->actingAs($this->user);

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        $inputs['source_control'] = $sourceControl->id;

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('create', $inputs)
            ->assertHasNoActionErrors()
            ->assertSuccessful();

        $expectedUser = empty($inputs['user']) ? $this->server->getSshUser() : $inputs['user'];
        $this->assertDatabaseHas('sites', [
            'domain' => $inputs['domain'],
            'aliases' => json_encode($inputs['aliases'] ?? []),
            'status' => SiteStatus::READY,
            'user' => $expectedUser,
            'path' => '/home/'.$expectedUser.'/'.$inputs['domain'],
        ]);
    }

    /**
     * @dataProvider failure_create_data
     */
    public function test_isolated_user_failure(array $inputs): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('create', $inputs)
            ->assertHasActionErrors();
    }

    /**
     * @dataProvider create_failure_data
     */
    public function test_create_site_failed_due_to_source_control(int $status): void
    {
        $inputs = [
            'type' => SiteType::LARAVEL,
            'domain' => 'example.com',
            'aliases' => ['www.example.com'],
            'php_version' => '8.2',
            'web_directory' => 'public',
            'repository' => 'test/test',
            'branch' => 'main',
            'composer' => true,
        ];

        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], $status),
        ]);

        $this->actingAs($this->user);

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        $inputs['source_control'] = $sourceControl->id;

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('create', $inputs)
            ->assertNotified()
            ->assertSuccessful();

        $this->assertDatabaseMissing('sites', [
            'domain' => 'example.com',
            'status' => SiteStatus::READY,
        ]);
    }

    public function test_see_sites_list(): void
    {
        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(Index::getUrl(['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee($site->domain);
    }

    public function test_delete_site(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(Settings::class, [
            'server' => $this->server,
            'site' => $site,
        ])
            ->callAction('delete')
            ->assertHasNoActionErrors()
            ->assertSuccessful();

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

        Livewire::test(SiteDetails::class, [
            'site' => $site,
        ])
            ->callInfolistAction('php_version', 'edit_php_version', [
                'version' => '8.2',
            ])
            ->assertHasNoActionErrors()
            ->assertSuccessful();

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

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        Livewire::test(SiteDetails::class, [
            'site' => $this->site,
        ])
            ->callInfolistAction('source_control_id', 'edit_source_control', [
                'source_control' => $sourceControl->id,
            ])
            ->assertHasNoActionErrors()
            ->assertSuccessful();

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

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        Livewire::test(SiteDetails::class, [
            'site' => $this->site,
        ])
            ->callInfolistAction('source_control_id', 'edit_source_control', [
                'source_control' => $sourceControl->id,
            ])
            ->assertNotified('Repository not found');
    }

    public function test_update_v_host(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(Settings::class, [
            'server' => $this->server,
            'site' => $site,
        ])
            ->callAction('vhost', [
                'vhost' => 'test',
            ])
            ->assertNotified('VHost updated!');
    }

    public function test_see_logs(): void
    {
        $this->actingAs($this->user);

        $this->get(View::getUrl([
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertSee('Logs');
    }

    public static function failure_create_data(): array
    {
        return [
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'a',
                ],
            ],
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'root',
                ],
            ],
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'vito',
                ],
            ],
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => '123',
                ],
            ],
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'qwertyuiopasdfghjklzxcvbnmqwertyu',
                ],
            ],
        ];
    }

    public static function create_data(): array
    {
        return [
            [
                [
                    'type' => SiteType::LARAVEL,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com', 'www2.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'composer' => true,
                ],
            ],
            [
                [
                    'type' => SiteType::LARAVEL,
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
                    'type' => SiteType::WORDPRESS,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'title' => 'Example',
                    'username' => 'example',
                    'email' => 'email@example.com',
                    'password' => 'password',
                    'database' => 'example',
                    'database_user' => 'example',
                    'database_password' => 'password',
                ],
            ],
            [
                [
                    'type' => SiteType::WORDPRESS,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'title' => 'Example',
                    'username' => 'example',
                    'email' => 'email@example.com',
                    'password' => 'password',
                    'database' => 'example',
                    'database_user' => 'example',
                    'database_password' => 'password',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                ],
            ],
            [
                [
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => SiteType::PHPMYADMIN,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'version' => '5.1.2',
                ],
            ],
            [
                [
                    'type' => SiteType::PHPMYADMIN,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'php_version' => '8.2',
                    'version' => '5.1.2',
                    'user' => 'example',
                ],
            ],
            [
                [
                    'type' => SiteType::LOAD_BALANCER,
                    'domain' => 'example.com',
                    'aliases' => ['www.example.com'],
                    'user' => 'example',
                    'method' => LoadBalancerMethod::ROUND_ROBIN,
                ],
            ],
        ];
    }

    public static function create_failure_data(): array
    {
        return [
            [401],
            [403],
            [404],
        ];
    }
}
