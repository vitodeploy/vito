<?php

namespace Tests\Feature;

use App\Enums\CronjobStatus;
use App\Http\Livewire\Cronjobs\CreateCronjob;
use App\Http\Livewire\Cronjobs\CronjobsList;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_cronjobs_list()
    {
        $this->actingAs($this->user);

        /** @var CronJob $cronjob */
        $cronjob = CronJob::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(CronjobsList::class, ['server' => $this->server])
            ->assertSeeText($cronjob->frequency_label);
    }

    public function test_delete_cronjob()
    {
        $this->actingAs($this->user);

        /** @var CronJob $cronjob */
        $cronjob = CronJob::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(CronjobsList::class, ['server' => $this->server])
            ->set('deleteId', $cronjob->id)
            ->call('delete')
            ->assertDispatched('confirmed');
    }

    public function test_create_cronjob()
    {
        $this->actingAs($this->user);

        Livewire::test(CreateCronjob::class, ['server' => $this->server])
            ->set('command', 'ls -la')
            ->set('user', 'vito')
            ->set('frequency', '* * * * *')
            ->call('create')
            ->assertDispatched('created');

        $this->assertDatabaseHas('cron_jobs', [
            'server_id' => $this->server->id,
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
            'status' => CronjobStatus::CREATING,
        ]);
    }
}
