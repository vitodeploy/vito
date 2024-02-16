<?php

namespace Tests\Feature;

use App\Enums\Database;
use App\Enums\OperatingSystem;
use App\Enums\ServerProvider;
use App\Enums\ServerStatus;
use App\Enums\Webserver;
use App\Http\Livewire\Servers\CreateServer;
use App\Jobs\Installation\Initialize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_custom_server(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        Livewire::test(CreateServer::class)
            ->set('provider', ServerProvider::CUSTOM)
            ->set('name', 'test')
            ->set('ip', '1.1.1.1')
            ->set('port', '22')
            ->set('os', OperatingSystem::UBUNTU22)
            ->set('webserver', Webserver::NGINX)
            ->set('database', Database::MYSQL80)
            ->set('php', '8.2')
            ->call('submit')
            ->assertSuccessful();

        $this->assertDatabaseHas('servers', [
            'name' => 'test',
            'ip' => '1.1.1.1',
            'status' => ServerStatus::INSTALLING,
        ]);

        Bus::assertDispatched(Initialize::class);
    }
}
