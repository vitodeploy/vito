<?php

use App\Facades\SSH;
use App\Models\Service;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('renders catalogue for installed services', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('logs.services', $this->server));

    $response->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('server-logs/services')
            ->where('title', 'Service logs')
            ->has('catalogue')
        );

    $catalogue = $response->viewData('page')['props']['catalogue'];
    $keys = array_column($catalogue, 'key');

    expect($keys)->toContain('nginx:error');
    expect($keys)->toContain('nginx:access');
    expect($keys)->toContain('mysql:journal');
    expect($keys)->toContain('php:8.2:fpm-journal');
    expect($keys)->toContain('ufw:general');
    expect($keys)->toContain('supervisor:general');
    expect($keys)->toContain('redis:journal');
    expect($keys)->toContain('system:sshd');
    expect($keys)->toContain('php:8.2:user:vito');
});

test('skips services without a registered handler', function () {
    $this->actingAs($this->user);

    Service::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'vpn',
        'name' => 'wireguard',
    ]);

    $response = $this->get(route('logs.services', $this->server));

    $response->assertSuccessful();

    $catalogue = $response->viewData('page')['props']['catalogue'];
    $keys = array_column($catalogue, 'key');

    expect($keys)->toContain('nginx:error');
    expect(array_filter($keys, fn (string $key): bool => str_starts_with($key, 'wireguard')))->toBeEmpty();
});

test('nginx exposes per site error log', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('logs.services', $this->server));

    $catalogue = $response->viewData('page')['props']['catalogue'];
    $entries = collect($catalogue)->keyBy('key');

    $key = 'nginx:site:'.$this->site->id.':error';
    expect($entries->has($key))->toBeTrue();
    expect($entries[$key]['display_target'])->toBe('/var/log/nginx/'.$this->site->domain.'-error.log');
});

test('services without has logs are skipped', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('logs.services', $this->server));
    $catalogue = $response->viewData('page')['props']['catalogue'];
    $serviceLabels = array_unique(array_column($catalogue, 'service_label'));

    expect($serviceLabels)->not->toContain('Node.js');
    expect($serviceLabels)->not->toContain('NodeJS');
});

test('read with unknown key returns 404', function () {
    $this->actingAs($this->user);
    SSH::fake();

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nope:does-not-exist',
    ])->assertNotFound();
});

test('read happy path for file source', function () {
    $this->actingAs($this->user);
    SSH::fake('nginx-error-output');

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nginx:error',
    ])
        ->assertSuccessful()
        ->assertJson([
            'content' => 'nginx-error-output',
            'display_target' => '/var/log/nginx/error.log',
            'source' => 'file',
        ]);

    SSH::assertExecutedContains('/var/log/nginx/error.log');
});

test('read returns 404 when file missing', function () {
    $this->actingAs($this->user);
    SSH::fake('VITO_NO_FILE');

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nginx:error',
    ])
        ->assertNotFound()
        ->assertJson(['message' => 'The log file does not exist on the server.']);
});

test('read journal source uses journalctl', function () {
    $this->actingAs($this->user);
    SSH::fake('mysql-journal-output');

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'mysql:journal',
    ])->assertSuccessful();

    SSH::assertExecutedContains("journalctl -u 'mysql.service'");
});

test('read rejects lines above max', function () {
    $this->actingAs($this->user);

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nginx:error',
        'lines' => 3000,
    ])->assertStatus(422);
});

test('read rejects search with newline', function () {
    $this->actingAs($this->user);

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nginx:error',
        'search' => "abc\ninjected",
    ])->assertStatus(422);
});

test('read with search shellescapes term', function () {
    $this->actingAs($this->user);
    SSH::fake('match');

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nginx:error',
        'search' => "needle's quote",
    ])->assertSuccessful();

    SSH::assertExecutedContains("'needle'\\''s quote'");
});

test('clear truncates file source', function () {
    $this->actingAs($this->user);
    SSH::fake();

    $this->post(route('logs.services.clear', $this->server), [
        'key' => 'nginx:error',
    ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Log cleared successfully');

    SSH::assertExecutedContains('/var/log/nginx/error.log');
});

test('clear rejects journal source', function () {
    $this->actingAs($this->user);
    SSH::fake();

    $this->postJson(route('logs.services.clear', $this->server), [
        'key' => 'mysql:journal',
    ])->assertStatus(422);
});

test('download invokes ssh download', function () {
    $this->actingAs($this->user);
    Bus::fake();
    Queue::fake();
    Carbon::setTestNow(Carbon::create(2026, 1, 1, 12));
    Str::createRandomStringsUsing(fn (int $n): string => str_repeat('a', $n));
    SSH::fake();

    $tmpName = $this->server->id.'-'.Carbon::now()->timestamp.'-aaaaaaaa-'.Str::slug('nginx:error').'.log';
    Storage::disk('local')->put($tmpName, 'pretend-downloaded-bytes');

    try {
        $this->get(route('logs.services.download', ['server' => $this->server, 'key' => 'nginx:error']))
            ->assertSuccessful();

        SSH::assertExecutedContains("sudo cat '/var/log/nginx/error.log'");
    } finally {
        Storage::disk('local')->delete($tmpName);
        Str::createRandomStringsNormally();
        Carbon::setTestNow();
    }
});

test('download journal source', function () {
    $this->actingAs($this->user);
    Bus::fake();
    Queue::fake();
    Carbon::setTestNow(Carbon::create(2026, 1, 1, 12));
    Str::createRandomStringsUsing(fn (int $n): string => str_repeat('a', $n));
    SSH::fake();

    $tmpName = $this->server->id.'-'.Carbon::now()->timestamp.'-aaaaaaaa-'.Str::slug('mysql:journal').'.log';
    Storage::disk('local')->put($tmpName, 'pretend-downloaded-bytes');

    try {
        $this->get(route('logs.services.download', ['server' => $this->server, 'key' => 'mysql:journal']))
            ->assertSuccessful();

        SSH::assertExecutedContains("sudo journalctl -u 'mysql.service'");
    } finally {
        Storage::disk('local')->delete($tmpName);
        Str::createRandomStringsNormally();
        Carbon::setTestNow();
    }
});

test('download returns 404 when file missing', function () {
    $this->actingAs($this->user);
    SSH::fake('VITO_NO_FILE');

    $this->getJson(route('logs.services.download', ['server' => $this->server, 'key' => 'nginx:error']))
        ->assertNotFound()
        ->assertJson(['message' => 'The log file does not exist on the server.']);
});

test('unknown key on download returns 404', function () {
    $this->actingAs($this->user);
    SSH::fake();

    $this->get(route('logs.services.download', ['server' => $this->server, 'key' => 'nope']))
        ->assertNotFound();
});

test('multiple sites per user deduped', function () {
    $this->actingAs($this->user);

    /** @var SourceControl $sc */
    $sc = SourceControl::factory()->github()->create();
    Site::factory()->create([
        'domain' => 'second.test',
        'server_id' => $this->server->id,
        'source_control_id' => $sc->id,
        'repository' => 'organization/repository',
        'path' => '/home/vito/second.test',
        'branch' => 'main',
        'php_version' => '8.2',
        'user' => 'vito',
    ]);

    $response = $this->get(route('logs.services', $this->server));
    $catalogue = $response->viewData('page')['props']['catalogue'];
    $userEntries = array_values(array_filter($catalogue, fn ($e) => $e['key'] === 'php:8.2:user:vito'));

    expect($userEntries)->toHaveCount(1);
    $this->assertStringContainsString('vito.test', $userEntries[0]['label']);
    $this->assertStringContainsString('second.test', $userEntries[0]['label']);
});

test('unauthorized user cannot view', function () {
    /** @var User $other */
    $other = User::factory()->create();
    $this->actingAs($other);

    $this->get(route('logs.services', $this->server))->assertForbidden();
});

test('unauthorized user cannot clear', function () {
    /** @var User $other */
    $other = User::factory()->create();
    $this->actingAs($other);
    SSH::fake();

    $this->post(route('logs.services.clear', $this->server), [
        'key' => 'nginx:error',
    ])->assertForbidden();
});

test('unauthorized user cannot read', function () {
    /** @var User $other */
    $other = User::factory()->create();
    $this->actingAs($other);
    SSH::fake();

    $this->postJson(route('logs.services.read', $this->server), [
        'key' => 'nginx:error',
    ])->assertForbidden();
});

test('unauthorized user cannot download', function () {
    /** @var User $other */
    $other = User::factory()->create();
    $this->actingAs($other);
    SSH::fake();

    $this->get(route('logs.services.download', ['server' => $this->server, 'key' => 'nginx:error']))
        ->assertForbidden();
});
