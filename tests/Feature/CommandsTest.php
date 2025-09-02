<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_commands(): void
    {
        $this->actingAs($this->user);

        $this->get(route('commands', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('commands/index'));
    }

    public function test_create_command(): void
    {
        $this->actingAs($this->user);

        $this->post(route('commands.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->put(route('commands.update', [
            'server' => $this->server,
            'site' => $this->site,
            'command' => $command,
        ]), [
            'name' => 'Updated Command',
            'command' => 'ls -la',
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->delete(route('commands.destroy', [
            'server' => $this->server,
            'site' => $this->site,
            'command' => $command,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('commands', [
            'id' => $command->id,
        ]);
    }

    public function test_execute_command(): void
    {
        SSH::fake('echo "Hello, world!"');

        $this->actingAs($this->user);

        /** @var Command $command */
        $command = $this->site->commands()->create([
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);

        $this->post(route('commands.execute', [
            'server' => $this->server,
            'site' => $this->site,
            'command' => $command,
        ]), [
            'MESSAGE' => 'Hello, world!',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('command_executions', [
            'command_id' => $command->id,
            'variables' => $this->castAsJson(['MESSAGE' => 'Hello, world!']),
        ]);
    }

    public function test_execute_command_validation_error(): void
    {
        $this->actingAs($this->user);

        $command = $this->site->commands()->create([
            'name' => 'Test Command',
            'command' => 'echo "${MESSAGE}"',
        ]);

        $this->post(route('commands.execute', [
            'server' => $this->server,
            'site' => $this->site,
            'command' => $command,
        ]))
            ->assertSessionHasErrors();
    }
}
