<?php

namespace Tests\Feature;

use App\Enums\ScriptExecutionStatus;
use App\Facades\SSH;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\Site;
use App\Web\Pages\Scripts\Executions;
use App\Web\Pages\Scripts\Index;
use App\Web\Pages\Scripts\Widgets\ScriptExecutionsList;
use App\Web\Pages\Scripts\Widgets\ScriptsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_scripts(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee($script->name);
    }

    public function test_create_script(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Index::class)
            ->callAction('create', [
                'name' => 'Test Script',
                'content' => 'echo "Hello, World!"',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('scripts', [
            'name' => 'Test Script',
            'content' => 'echo "Hello, World!"',
        ]);
    }

    public function test_edit_script(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(ScriptsList::class)
            ->callTableAction('edit', $script->id, [
                'name' => 'New Name',
                'content' => 'echo "Hello, new World!"',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('scripts', [
            'id' => $script->id,
            'name' => 'New Name',
            'content' => 'echo "Hello, new World!"',
        ]);
    }

    public function test_delete_script(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $scriptExecution = ScriptExecution::factory()->create([
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::EXECUTING,
        ]);

        Livewire::test(ScriptsList::class)
            ->callTableAction('delete', $script->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('scripts', [
            'id' => $script->id,
        ]);

        $this->assertDatabaseMissing('script_executions', [
            'id' => $scriptExecution->id,
        ]);
    }

    public function test_execute_script_and_view_log(): void
    {
        SSH::fake('script output');

        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(Executions::class, [
            'script' => $script,
        ])
            ->callAction('execute', [
                'server' => $this->server->id,
                'user' => 'root',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
            'user' => 'root',
        ]);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
        ]);

        $execution = $script->lastExecution;

        Livewire::test(ScriptExecutionsList::class, [
            'script' => $script,
        ])
            ->callTableAction('logs', $execution->id)
            ->assertSuccessful();
    }

    public function test_execute_script_as_isolated_user(): void
    {
        SSH::fake('script output');

        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'example',
        ]);

        Livewire::test(Executions::class, [
            'script' => $script,
        ])
            ->callAction('execute', [
                'server' => $this->server->id,
                'user' => 'example',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
            'user' => 'example',
        ]);
    }

    public function test_cannot_execute_script_as_non_existing_user(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(Executions::class, [
            'script' => $script,
        ])
            ->callAction('execute', [
                'server' => $this->server->id,
                'user' => 'example',
            ])
            ->assertHasActionErrors();

        $this->assertDatabaseMissing('script_executions', [
            'script_id' => $script->id,
            'user' => 'example',
        ]);
    }

    public function test_cannot_execute_script_as_user_not_on_server(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Site::factory()->create([
            'server_id' => Server::factory()->create(['user_id' => 1])->id,
            'user' => 'example',
        ]);

        Livewire::test(Executions::class, [
            'script' => $script,
        ])
            ->callAction('execute', [
                'server' => $this->server->id,
                'user' => 'example',
            ])
            ->assertHasActionErrors();

        $this->assertDatabaseMissing('script_executions', [
            'script_id' => $script->id,
            'user' => 'example',
        ]);
    }

    public function test_see_executions(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $scriptExecution = ScriptExecution::factory()->create([
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::EXECUTING,
        ]);

        $this->get(Executions::getUrl(['script' => $script]))
            ->assertSuccessful()
            ->assertSee($scriptExecution->status);
    }
}
