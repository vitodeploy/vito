<?php

namespace Tests\Feature;

use App\Enums\Database;
use App\Enums\OperatingSystem;
use App\Enums\ServerProvider;
use App\Enums\ServerStatus;
use App\Enums\ServerType;
use App\Enums\Webserver;
use App\Jobs\Installation\Initialize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use JsonException;
use Tests\TestCase;

/**
 * @TODO add more tests
 */
class ServerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws JsonException
     */
    public function test_create_custom_server(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        $this->post(route('servers.create'), [
            'type' => ServerType::REGULAR,
            'provider' => ServerProvider::CUSTOM,
            'name' => 'test',
            'ip' => '1.1.1.1',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22,
            'webserver' => Webserver::NGINX,
            'database' => Database::MYSQL80,
            'php' => '8.2',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'test',
            'ip' => '1.1.1.1',
            'status' => ServerStatus::INSTALLING,
        ]);

        Bus::assertDispatched(Initialize::class);
    }
}
