<?php

namespace Tests\Unit\Actions\SSL;

use App\Actions\SSL\DeleteSsl;
use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Facades\SSH;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeleteSslTest extends TestCase
{
    use RefreshDatabase;

    private DeleteSsl $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new DeleteSsl;
    }

    public function test_deletes_server_level_ssl(): void
    {
        SSH::fake('SSL DELETED SUCCESSFULLY');

        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
        ]);

        $this->action->delete($ssl);

        $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    }

    public function test_deletes_site_level_ssl(): void
    {
        SSH::fake();

        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => SslStatus::CREATED,
        ]);

        $this->action->delete($ssl);

        $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    }

    public function test_prevents_deletion_when_in_use_by_hosted_domain(): void
    {
        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
            'ssl_id' => $ssl->id,
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->expectException(ValidationException::class);

        $this->action->delete($ssl);
    }

    public function test_allows_deletion_when_no_hosted_domains_reference_ssl(): void
    {
        SSH::fake('SSL DELETED SUCCESSFULLY');

        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
            'ssl_id' => null,
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->action->delete($ssl);

        $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    }

    public function test_deletion_dispatches_job_for_server_level(): void
    {
        \Illuminate\Support\Facades\Bus::fake();

        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
        ]);

        $this->action->delete($ssl);

        $this->assertDatabaseHas('ssls', [
            'id' => $ssl->id,
            'status' => SslStatus::DELETING,
        ]);

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SSL\DeleteServerSslJob::class);
    }
}
