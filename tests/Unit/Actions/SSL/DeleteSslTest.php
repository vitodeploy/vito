<?php

use App\Actions\SSL\DeleteSsl;
use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Facades\SSH;
use App\Jobs\SSL\DeleteServerSslJob;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new DeleteSsl;
});

test('deletes server level ssl', function () {
    SSH::fake('SSL DELETED SUCCESSFULLY');

    $ssl = Ssl::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => null,
        'status' => SslStatus::CREATED,
    ]);

    $this->action->delete($ssl);

    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
});

test('deletes site level ssl', function () {
    SSH::fake();

    $ssl = Ssl::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => SslStatus::CREATED,
    ]);

    $this->action->delete($ssl);

    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
});

test('prevents deletion when in use by hosted domain', function () {
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
});

test('allows deletion when no hosted domains reference ssl', function () {
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
});

test('deletion dispatches job for server level', function () {
    Bus::fake();

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

    Bus::assertDispatched(DeleteServerSslJob::class);
});
