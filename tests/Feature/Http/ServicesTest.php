<?php

namespace Tests\Feature\Http;

use App\Enums\ServiceStatus;
use App\Http\Livewire\Services\ServicesList;
use App\Jobs\Service\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->assertSee([
                'nginx',
                'php',
                'supervisor',
                'redis',
                'ufw',
                'php'
            ]);
    }

    /**
     * @dataProvider data
     */
    public function test_restart_service(string $name): void
    {
        $service = $this->server->services()->where('name', $name)->first();

        Bus::fake();

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('restart', $service->id)
            ->assertSuccessful();

        Bus::assertDispatched(Manage::class);
    }

    /**
     * @dataProvider data
     */
    public function test_stop_service(string $name): void
    {
        $service = $this->server->services()->where('name', $name)->first();

        Bus::fake();

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('stop', $service->id)
            ->assertSuccessful();

        Bus::assertDispatched(Manage::class);
    }

    /**
     * @dataProvider data
     */
    public function test_start_service(string $name): void
    {
        $service = $this->server->services()->where('name', $name)->first();

        $service->status = ServiceStatus::STOPPED;
        $service->save();

        Bus::fake();

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('start', $service->id)
            ->assertSuccessful();

        Bus::assertDispatched(Manage::class);
    }

    public static function data(): array
    {
        return [
            ['nginx'],
            ['php'],
            ['supervisor'],
            ['redis'],
            ['ufw'],
            ['php'],
        ];
    }
}
