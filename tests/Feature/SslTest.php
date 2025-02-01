<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\SSH;
use App\Models\Ssl;
use App\Web\Pages\Servers\Sites\Pages\SSL\Index;
use App\Web\Pages\Servers\Sites\Pages\SSL\Widgets\SslsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get(Index::getUrl([
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertSee($ssl->type);
    }

    public function test_letsencrypt_ssl()
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'type' => SslType::LETSENCRYPT,
                'email' => 'ssl@example.com',
            ])
            ->assertSuccessful();

        $ssl = Ssl::query()->where('site_id', $this->site->id)->first();
        $this->assertNotEmpty($ssl);

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'domains' => json_encode([$this->site->domain]),
            'email' => 'ssl@example.com',
            'certificate_path' => '/etc/letsencrypt/live/'.$ssl->id.'/fullchain.pem',
            'pk_path' => '/etc/letsencrypt/live/'.$ssl->id.'/privkey.pem',
        ]);
    }

    public function test_letsencrypt_ssl_with_aliases()
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'type' => SslType::LETSENCRYPT,
                'email' => 'ssl@example.com',
                'aliases' => true,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'domains' => json_encode(array_merge([$this->site->domain], $this->site->aliases)),
            'email' => 'ssl@example.com',
        ]);
    }

    public function test_custom_ssl()
    {
        SSH::fake('Successfully received certificate');

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'type' => SslType::CUSTOM,
                'certificate' => 'certificate',
                'private' => 'private',
                'expires_at' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertSuccessful();

        $ssl = Ssl::query()->where('site_id', $this->site->id)->first();
        $this->assertNotEmpty($ssl);

        $this->assertDatabaseHas('ssls', [
            'site_id' => $this->site->id,
            'type' => SslType::CUSTOM,
            'status' => SslStatus::CREATED,
            'domains' => json_encode([$this->site->domain]),
            'certificate_path' => '/etc/ssl/'.$ssl->id.'/cert.pem',
            'pk_path' => '/etc/ssl/'.$ssl->id.'/privkey.pem',
        ]);
    }

    public function test_delete_ssl()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'site_id' => $this->site->id,
        ]);

        Livewire::test(SslsList::class, [
            'site' => $this->site,
        ])
            ->callTableAction('delete', $ssl->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('ssls', [
            'id' => $ssl->id,
        ]);
    }
}
