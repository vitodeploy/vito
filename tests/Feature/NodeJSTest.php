<?php

namespace Tests\Feature;

use App\Enums\NodeJS;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use App\Web\Pages\Servers\NodeJS\Index;
use App\Web\Pages\Servers\NodeJS\Widgets\NodeJSList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NodeJSTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_new_nodejs(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Index::class, ['server' => $this->server])
            ->callAction('install', [
                'version' => NodeJS::V16,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'type' => 'nodejs',
            'version' => NodeJS::V16,
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_uninstall_nodejs(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $php = new Service([
            'server_id' => $this->server->id,
            'type' => 'nodejs',
            'type_data' => [],
            'name' => 'nodejs',
            'version' => NodeJS::V16,
            'status' => ServiceStatus::READY,
            'is_default' => true,
        ]);
        $php->save();

        Livewire::test(NodeJSList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('uninstall', $php->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('services', [
            'id' => $php->id,
        ]);
    }

    public function test_change_default_nodejs_cli(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        /** @var Service $service */
        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'nodejs',
            'type_data' => [],
            'name' => 'nodejs',
            'version' => NodeJS::V16,
            'status' => ServiceStatus::READY,
            'is_default' => false,
        ]);

        Livewire::test(NodeJSList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('default-nodejs-cli', $service->id)
            ->assertSuccessful();

        $service->refresh();

        $this->assertTrue($service->is_default);
    }
}
