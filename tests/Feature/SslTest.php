<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\SSH;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get(route('servers.sites.ssl', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertSee($ssl->type);
    }

    public function test_see_ssls_list_with_no_ssls()
    {
        $this->actingAs($this->user);

        $this->get(route('servers.sites.ssl', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertSeeText(__("You don't have any SSL certificates yet!"));
    }

    public function test_letsencrypt_ssl()
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        $this->post(route('servers.sites.ssl.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'type' => SslType::LETSENCRYPT,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'domains' => json_encode([$this->site->domain]),
        ]);
    }

    public function test_letsencrypt_ssl_with_aliases()
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        $this->post(route('servers.sites.ssl.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'type' => SslType::LETSENCRYPT,
            'aliases' => '1',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'domains' => json_encode(array_merge([$this->site->domain], $this->site->aliases)),
        ]);
    }

    public function test_custom_ssl()
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        $this->post(route('servers.sites.ssl.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'type' => SslType::CUSTOM,
            'certificate' => 'certificate',
            'private' => 'private',
            'expires_at' => now()->addYear()->format('Y-m-d'),
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::CUSTOM,
            'status' => SslStatus::CREATED,
        ]);
    }

    public function test_delete_ssl()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->delete(route('servers.sites.ssl.destroy', [
            'server' => $this->server,
            'site' => $this->site,
            'ssl' => $ssl,
        ]))->assertRedirect();

        $this->assertDatabaseMissing('ssls', [
            'id' => $ssl->id,
        ]);
    }
}
