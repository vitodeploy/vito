<?php

namespace Tests\Feature\Http;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Http\Livewire\Ssl\CreateSsl;
use App\Http\Livewire\Ssl\SslsList;
use App\Jobs\Ssl\Deploy;
use App\Jobs\Ssl\Remove;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class SslTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_ssls_list()
    {
        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        Livewire::test(SslsList::class, ['site' => $this->site])
            ->assertSeeText($ssl->type);
    }

    public function test_see_ssls_list_with_no_ssls()
    {
        $this->actingAs($this->user);

        Livewire::test(SslsList::class, ['site' => $this->site])
            ->assertSeeText(__("You don't have any SSL certificates yet!"));
    }

    public function test_create_ssl()
    {
        Bus::fake();

        $this->actingAs($this->user);

        Livewire::test(CreateSsl::class, ['site' => $this->site])
            ->set('type', SslType::LETSENCRYPT)
            ->call('create')
            ->assertDispatched('created');

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATING,
        ]);

        Bus::assertDispatched(Deploy::class);
    }

    public function test_delete_ssl()
    {
        Bus::fake();

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        Livewire::test(SslsList::class, ['site' => $this->site])
            ->set('deleteId', $ssl->id)
            ->call('delete')
            ->assertDispatched('confirmed');

        $this->assertDatabaseHas('ssls', [
            'id' => $ssl->id,
            'status' => SslStatus::DELETING,
        ]);

        Bus::assertDispatched(Remove::class);
    }
}
