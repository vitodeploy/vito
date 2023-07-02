<?php

namespace Tests\Feature\Http;

use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Enums\SourceControl;
use App\Http\Livewire\Sites\ChangePhpVersion;
use App\Http\Livewire\Sites\CreateSite;
use App\Http\Livewire\Sites\DeleteSite;
use App\Http\Livewire\Sites\SitesList;
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

    public function test_create_site(): void
    {
        Bus::fake();

        Http::fake();

        $this->actingAs($this->user);

        \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        Livewire::test(CreateSite::class, ['server' => $this->server])
            ->set('type', SiteType::LARAVEL)
            ->set('domain', 'example.com')
            ->set('alias', 'www.example.com')
            ->set('php_version', '8.2')
            ->set('web_directory', 'public')
            ->set('source_control', SourceControl::GITHUB)
            ->set('repository', 'test/test')
            ->set('branch', 'main')
            ->set('composer', true)
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
}
