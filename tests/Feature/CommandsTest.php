<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Web\Pages\Servers\Sites\View;
use App\Web\Pages\Servers\Sites\Widgets\Commands;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_commands(): void
    {
        $this->actingAs($this->user);

        $this->get(
            View::getUrl([
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee($this->site->domain)
            ->assertSee('Commands');
    }

    public function test_create_command(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Commands::class, ['site' => $this->site])
            ->assertTableHeaderActionsExistInOrder(['new-command'])
            ->callTableAction('new-command', null, [
                'name' => 'Test Command',
                'command' => 'echo "${MESSAGE}"',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('commands', [
            'site_id' => $this->site->id,
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);
    }

    public function test_edit_command(): void
    {
        $this->actingAs($this->user);

        $command = $this->site->commands()->create([
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);

        Livewire::test(Commands::class, ['site' => $this->site])
            ->callTableAction('edit', $command->id, [
                'name' => 'Updated Command',
                'command' => 'ls -la',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('commands', [
            'id' => $command->id,
            'site_id' => $this->site->id,
            'name' => 'Updated Command',
            'command' => 'ls -la',
        ]);
    }

    public function test_delete_command(): void
    {
        $this->actingAs($this->user);

        $command = $this->site->commands()->create([
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);

        Livewire::test(Commands::class, ['site' => $this->site])
            ->callTableAction('delete', $command->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('commands', [
            'id' => $command->id,
        ]);
    }

    public function test_execute_command(): void
    {
        SSH::fake('echo "Hello, world!"');

        $this->actingAs($this->user);

        $command = $this->site->commands()->create([
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);

        Livewire::test(Commands::class, ['site' => $this->site])
            ->callTableAction('execute', $command->id, [
                'variables' => [
                    'MESSAGE' => 'Hello, world!',
                ],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('command_executions', [
            'command_id' => $command->id,
            'variables' => json_encode(['MESSAGE' => 'Hello, world!']),
        ]);
    }

    public function test_execute_command_validation_error(): void
    {
        $this->actingAs($this->user);

        $command = $this->site->commands()->create([
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);

        Livewire::test(Commands::class, ['site' => $this->site])
            ->callTableAction('execute', $command->id, [])
            ->assertHasActionErrors();
    }
}
