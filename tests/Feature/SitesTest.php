<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Enums\SourceControl;
use App\Facades\SSH;
use App\Http\Livewire\Sites\ChangePhpVersion;
use App\Http\Livewire\Sites\CreateSite;
use App\Http\Livewire\Sites\DeleteSite;
use App\Http\Livewire\Sites\SitesList;
use App\Http\Livewire\Sites\UpdateSourceControlProvider;
use App\Http\Livewire\Sites\UpdateVHost;
use App\Jobs\Site\CreateVHost;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
        Bus::fake();

        Http::fake();

        $this->actingAs($this->user);

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        Livewire::test(CreateSite::class, ['server' => $this->server])
            ->fill($inputs)
            ->set('inputs.source_control', $sourceControl->id)
            ->call('create')
            ->assertSuccessful()
            ->assertHasNoErrors();

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

        Livewire::test(SitesList::class, ['server' => $this->server])
            ->assertSee([
                $site->domain,
            ]);
    }

    public function test_delete_site(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(DeleteSite::class, ['server' => $this->server])
            ->set('site', $site)
            ->call('delete')
            ->assertSuccessful();

        Bus::assertDispatched(\App\Jobs\Site\DeleteSite::class);

        $site->refresh();

        $this->assertEquals(SiteStatus::DELETING, $site->status);
    }

    public function test_change_php_version(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ChangePhpVersion::class, ['site' => $site])
            ->set('version', '8.1')
            ->call('change')
            ->assertSuccessful();

        Bus::assertDispatched(\App\Jobs\Site\ChangePHPVersion::class);
    }

    public function test_update_v_host(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(UpdateVHost::class, ['site' => $site])
            ->set('vHost', 'test-vhost')
            ->call('update')
            ->assertSuccessful();
    }

    public function test_update_source_control(): void
    {
        $this->actingAs($this->user);

        /** @var \App\Models\SourceControl $gitlab */
        $gitlab = \App\Models\SourceControl::factory()->gitlab()->create();

        Livewire::test(UpdateSourceControlProvider::class, ['site' => $this->site])
            ->set('source_control', $gitlab->id)
            ->call('update')
            ->assertSuccessful();

        $this->site->refresh();

        $this->assertEquals($gitlab->id, $this->site->source_control_id);
    }

    public static function create_data(): array
    {
        return [
            [
                [
                    'inputs.type' => SiteType::LARAVEL,
                    'inputs.domain' => 'example.com',
                    'inputs.alias' => 'www.example.com',
                    'inputs.php_version' => '8.2',
                    'inputs.web_directory' => 'public',
                    'inputs.repository' => 'test/test',
                    'inputs.branch' => 'main',
                    'inputs.composer' => true,
                ],
            ],
            [
                [
                    'inputs.type' => SiteType::WORDPRESS,
                    'inputs.domain' => 'example.com',
                    'inputs.alias' => 'www.example.com',
                    'inputs.php_version' => '8.2',
                    'inputs.title' => 'Example',
                    'inputs.username' => 'example',
                    'inputs.email' => 'email@example.com',
                    'inputs.password' => 'password',
                    'inputs.database' => 'example',
                    'inputs.database_user' => 'example',
                    'inputs.database_password' => 'password',
                ],
            ],
            [
                [
                    'inputs.type' => SiteType::PHP_BLANK,
                    'inputs.domain' => 'example.com',
                    'inputs.alias' => 'www.example.com',
                    'inputs.php_version' => '8.2',
                    'inputs.web_directory' => 'public',
                ],
            ],
        ];
    }
}
