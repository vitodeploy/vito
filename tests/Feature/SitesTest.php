<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Enums\SourceControl;
use App\Facades\SSH;
use App\Jobs\Site\CreateVHost;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SitesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider create_data
     */
    public function test_create_site(array $inputs): void
    {
        Bus::fake();

        Http::fake();

        $this->actingAs($this->user);

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        $inputs['source_control'] = $sourceControl->id;

        $this->post(route('servers.sites.create', [
            'server' => $this->server,
        ]), $inputs)->assertSessionDoesntHaveErrors();

        Bus::assertDispatched(CreateVHost::class);

        $this->assertDatabaseHas('sites', [
            'domain' => 'example.com',
            'status' => SiteStatus::INSTALLING,
        ]);
    }

    public function test_see_sites_list(): void
    {
        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('servers.sites', [
            'server' => $this->server,
        ]))
            ->assertOk()
            ->assertSee($site->domain);
    }

    public function test_delete_site(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('servers.sites.destroy', [
            'server' => $this->server,
            'site' => $site,
        ]))->assertRedirect();

        Bus::assertDispatched(\App\Jobs\Site\DeleteSite::class);

        $site->refresh();

        $this->assertEquals(SiteStatus::DELETING, $site->status);
    }

    public function test_change_php_version(): void
    {
        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->post(route('servers.sites.settings.php', [
            'server' => $this->server,
            'site' => $site,
        ]), [
            'version' => '8.2',
        ])->assertSessionDoesntHaveErrors();

        $site->refresh();

        $this->assertEquals('8.2', $site->php_version);
    }

    public function test_update_v_host(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('servers.sites.settings.vhost', [
            'server' => $this->server,
            'site' => $site,
        ]))->assertSessionHasNoErrors();
    }

    public static function create_data(): array
    {
        return [
            [
                [
                    'type' => SiteType::LARAVEL,
                    'domain' => 'example.com',
                    'alias' => 'www.example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                    'repository' => 'test/test',
                    'branch' => 'main',
                    'composer' => true,
                ],
            ],
            [
                [
                    'type' => SiteType::WORDPRESS,
                    'domain' => 'example.com',
                    'alias' => 'www.example.com',
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
                    'type' => SiteType::PHP_BLANK,
                    'domain' => 'example.com',
                    'alias' => 'www.example.com',
                    'php_version' => '8.2',
                    'web_directory' => 'public',
                ],
            ],
        ];
    }
}
