<?php

namespace Tests\Feature;

use App\Facades\SSH;
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
use Tests\TestCase;

class ServiceLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_catalogue_for_installed_services(): void
    {
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

        $this->assertContains('nginx:error', $keys);
        $this->assertContains('nginx:access', $keys);
        $this->assertContains('mysql:journal', $keys);
        $this->assertContains('php:8.2:fpm-journal', $keys);
        $this->assertContains('ufw:general', $keys);
        $this->assertContains('supervisor:general', $keys);
        $this->assertContains('redis:journal', $keys);
        $this->assertContains('system:sshd', $keys);
        $this->assertContains('php:8.2:user:vito', $keys);
    }

    public function test_services_without_has_logs_are_skipped(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('logs.services', $this->server));
        $catalogue = $response->viewData('page')['props']['catalogue'];
        $serviceLabels = array_unique(array_column($catalogue, 'service_label'));

        $this->assertNotContains('Node.js', $serviceLabels);
        $this->assertNotContains('NodeJS', $serviceLabels);
    }

    public function test_read_with_unknown_key_returns_404(): void
    {
        $this->actingAs($this->user);
        SSH::fake();

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'nope:does-not-exist',
        ])->assertNotFound();
    }

    public function test_read_happy_path_for_file_source(): void
    {
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
    }

    public function test_read_returns_404_when_file_missing(): void
    {
        $this->actingAs($this->user);
        SSH::fake('VITO_NO_FILE');

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'nginx:error',
        ])
            ->assertNotFound()
            ->assertJson(['message' => 'The log file does not exist on the server.']);
    }

    public function test_read_journal_source_uses_journalctl(): void
    {
        $this->actingAs($this->user);
        SSH::fake('mysql-journal-output');

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'mysql:journal',
        ])->assertSuccessful();

        SSH::assertExecutedContains("journalctl -u 'mysql.service'");
    }

    public function test_read_rejects_lines_above_max(): void
    {
        $this->actingAs($this->user);

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'nginx:error',
            'lines' => 3000,
        ])->assertStatus(422);
    }

    public function test_read_rejects_search_with_newline(): void
    {
        $this->actingAs($this->user);

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'nginx:error',
            'search' => "abc\ninjected",
        ])->assertStatus(422);
    }

    public function test_read_with_search_shellescapes_term(): void
    {
        $this->actingAs($this->user);
        SSH::fake('match');

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'nginx:error',
            'search' => "needle's quote",
        ])->assertSuccessful();

        SSH::assertExecutedContains("'needle'\\''s quote'");
    }

    public function test_clear_truncates_file_source(): void
    {
        $this->actingAs($this->user);
        SSH::fake();

        $this->post(route('logs.services.clear', $this->server), [
            'key' => 'nginx:error',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Log cleared successfully');

        SSH::assertExecutedContains('/var/log/nginx/error.log');
    }

    public function test_clear_rejects_journal_source(): void
    {
        $this->actingAs($this->user);
        SSH::fake();

        $this->postJson(route('logs.services.clear', $this->server), [
            'key' => 'mysql:journal',
        ])->assertStatus(422);
    }

    public function test_download_invokes_ssh_download(): void
    {
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
    }

    public function test_download_journal_source(): void
    {
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
    }

    public function test_download_returns_404_when_file_missing(): void
    {
        $this->actingAs($this->user);
        SSH::fake('VITO_NO_FILE');

        $this->getJson(route('logs.services.download', ['server' => $this->server, 'key' => 'nginx:error']))
            ->assertNotFound()
            ->assertJson(['message' => 'The log file does not exist on the server.']);
    }

    public function test_unknown_key_on_download_returns_404(): void
    {
        $this->actingAs($this->user);
        SSH::fake();

        $this->get(route('logs.services.download', ['server' => $this->server, 'key' => 'nope']))
            ->assertNotFound();
    }

    public function test_multiple_sites_per_user_deduped(): void
    {
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

        $this->assertCount(1, $userEntries);
        $this->assertStringContainsString('vito.test', $userEntries[0]['label']);
        $this->assertStringContainsString('second.test', $userEntries[0]['label']);
    }

    public function test_unauthorized_user_cannot_view(): void
    {
        /** @var User $other */
        $other = User::factory()->create();
        $this->actingAs($other);

        $this->get(route('logs.services', $this->server))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_clear(): void
    {
        /** @var User $other */
        $other = User::factory()->create();
        $this->actingAs($other);
        SSH::fake();

        $this->post(route('logs.services.clear', $this->server), [
            'key' => 'nginx:error',
        ])->assertForbidden();
    }

    public function test_unauthorized_user_cannot_read(): void
    {
        /** @var User $other */
        $other = User::factory()->create();
        $this->actingAs($other);
        SSH::fake();

        $this->postJson(route('logs.services.read', $this->server), [
            'key' => 'nginx:error',
        ])->assertForbidden();
    }

    public function test_unauthorized_user_cannot_download(): void
    {
        /** @var User $other */
        $other = User::factory()->create();
        $this->actingAs($other);
        SSH::fake();

        $this->get(route('logs.services.download', ['server' => $this->server, 'key' => 'nginx:error']))
            ->assertForbidden();
    }
}
