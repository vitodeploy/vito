<?php

namespace Feature;

use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Web\Pages\Servers\Sites\Widgets\Commands as CommandsWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommandTest extends TestCase
{
    use RefreshDatabase;

    protected Server $server;

    protected Site $site;

    protected User $user;

    private Command $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create();
        $this->site = Site::factory()->create(['server_id' => $this->server->id]);
        $this->command = Command::factory()->create([
            'site_id' => $this->site->id,
            'name' => 'Test Command',
            'command' => 'php artisan test',
        ]);

        $this->actingAs($this->user); // Ensure the user is authenticated for all tests
    }

    /** @test */
    public function it_can_list_commands()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->assertCanSeeTableRecords([$this->command])
            ->assertCanSeeTableColumn('name')
            ->assertSee($this->command->name);
    }

    /** @test */
    public function it_can_create_command()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('new-command', [
                'name' => 'New Command',
                'command' => 'php artisan new:command',
            ]);

        $this->assertDatabaseHas('commands', [
            'site_id' => $this->site->id,
            'name' => 'New Command',
            'command' => 'php artisan new:command',
        ]);
    }

    /** @test */
    public function it_can_edit_command()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('edit', [
                'name' => 'Updated Command',
                'command' => 'php artisan updated:command',
            ], $this->command);

        $this->assertDatabaseHas('commands', [
            'id' => $this->command->id,
            'name' => 'Updated Command',
            'command' => 'php artisan updated:command',
        ]);
    }

    /** @test */
    public function it_can_delete_command()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('delete', [], $this->command);

        $this->assertDatabaseMissing('commands', [
            'id' => $this->command->id,
        ]);
    }

    /** @test */
    public function it_can_execute_command()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('execute', [], $this->command);

        $this->assertDatabaseHas('command_executions', [
            'command_id' => $this->command->id,
        ]);
    }

    /** @test */
    public function it_can_execute_command_with_variables()
    {
        $command = Command::factory()->create([
            'site_id' => $this->site->id,
            'name' => 'Command with Variables',
            'command' => 'php artisan test ${ENV} ${MODE}',
        ]);

        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('execute', [
                'variables' => [
                    'ENV' => 'production',
                    'MODE' => 'debug',
                ],
            ], $command);

        $this->assertDatabaseHas('command_executions', [
            'command_id' => $command->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('new-command', [
                'name' => '',
                'command' => '',
            ])
            ->assertHasTableActionErrors(['name', 'command']);
    }

    /** @test */
    public function it_validates_required_fields_when_editing()
    {
        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('edit', [
                'name' => '',
                'command' => '',
            ], $this->command)
            ->assertHasTableActionErrors(['name', 'command']);
    }

    /** @test */
    public function it_shows_command_execution_status()
    {
        $execution = CommandExecution::factory()->create([
            'command_id' => $this->command->id,
            'status' => 'completed',
        ]);

        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->assertCanSeeTableColumn('lastExecution.status')
            ->assertSee('completed');
    }

    /** @test */
    public function unauthorized_user_cannot_create_command()
    {
        $unauthorizedUser = User::factory()->create();

        $this->actingAs($unauthorizedUser);

        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->assertTableActionHidden('new-command');
    }

    /** @test */
    public function unauthorized_user_cannot_edit_command()
    {
        $unauthorizedUser = User::factory()->create();

        $this->actingAs($unauthorizedUser);

        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->assertTableActionHidden('edit', $this->command);
    }

    /** @test */
    public function unauthorized_user_cannot_delete_command()
    {
        $unauthorizedUser = User::factory()->create();

        $this->actingAs($unauthorizedUser);

        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->assertTableActionHidden('delete', $this->command);
    }

    /** @test */
    public function it_can_view_command_logs()
    {
        $execution = CommandExecution::factory()->create([
            'command_id' => $this->command->id,
        ]);

        Livewire::test(CommandsWidget::class, ['site' => $this->site])
            ->callTableAction('logs', [], $this->command)
            ->assertSuccessful();
    }
}
