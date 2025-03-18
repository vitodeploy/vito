<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\Redirect;
use App\Web\Pages\Servers\Sites\Pages\Redirects\Index;
use App\Web\Pages\Servers\Sites\Pages\Redirects\Widgets\RedirectsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_redirects(): void
    {
        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->get(
            Index::getUrl([
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee($redirect->from);
    }

    public function test_delete_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        Livewire::test(RedirectsList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('delete', $redirect->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('redirects', [
            'id' => $redirect->id,
        ]);
    }

    public function test_create_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'from' => 'some-path',
                'to' => 'https://example.com/redirect',
                'mode' => 301,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('redirects', [
            'from' => 'some-path',
            'to' => 'https://example.com/redirect',
            'mode' => 301,
        ]);
    }
}
