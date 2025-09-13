<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\SSH;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SslTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_ssls_list(): void
    {
        $this->actingAs($this->user);

        Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->get(route('ssls', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('ssls/index'));

    }

    public function test_letsencrypt_ssl(): void
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        $this->post(route('ssls.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'type' => SslType::LETSENCRYPT->value,
            'email' => 'ssl@example.com',
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $ssl = Ssl::query()->where('site_id', $this->site->id)->first();
        $this->assertNotEmpty($ssl);

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'domains' => $this->castAsJson([$this->site->domain]),
            'email' => 'ssl@example.com',
            'certificate_path' => '/etc/letsencrypt/live/'.$ssl->id.'/fullchain.pem',
            'pk_path' => '/etc/letsencrypt/live/'.$ssl->id.'/privkey.pem',
        ]);
    }

    public function test_letsencrypt_ssl_with_aliases(): void
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        $this->post(route('ssls.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'type' => SslType::LETSENCRYPT->value,
            'email' => 'ssl@example.com',
            'aliases' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'domains' => $this->castAsJson(array_merge([$this->site->domain], $this->site->aliases)),
            'email' => 'ssl@example.com',
        ]);
    }

    public function test_custom_ssl(): void
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        $this->post(route('ssls.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'type' => SslType::CUSTOM->value,
            'certificate' => 'certificate',
            'private' => 'private',
            'expires_at' => now()->addYear()->format('Y-m-d'),
        ])
            ->assertSessionDoesntHaveErrors();

        $ssl = Ssl::query()->where('site_id', $this->site->id)->first();
        $this->assertNotEmpty($ssl);

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::CUSTOM,
            'status' => SslStatus::CREATED,
            'domains' => $this->castAsJson([$this->site->domain]),
            'certificate_path' => '/etc/ssl/'.$ssl->id.'/cert.pem',
            'pk_path' => '/etc/ssl/'.$ssl->id.'/privkey.pem',
        ]);
    }

    public function test_delete_ssl(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->delete(route('ssls.destroy', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'ssl' => $ssl->id,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('ssls', [
            'id' => $ssl->id,
        ]);
    }
}
