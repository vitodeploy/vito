<?php

namespace Tests\Feature;

use App\Enums\ScriptExecutionStatus;
use App\Facades\SSH;
use App\Models\Script;
use App\Models\ScriptExecution;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get(
            route('scripts.index')
        )
            ->assertSuccessful()
            ->assertSee($script->name);
    }

    public function test_create_script(): void
    {
        $this->actingAs($this->user);

        $this->post(
            route('scripts.store'),
            [
                'name' => 'Test Script',
                'content' => 'echo "Hello, World!"',
            ]
        )
            ->assertSessionDoesntHaveErrors()
            ->assertHeader('HX-Redirect');

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

        $this->post(route('scripts.edit', ['script' => $script]), [
            'name' => 'New Name',
            'content' => 'echo "Hello, new World!"',
        ])
            ->assertSuccessful()
            ->assertHeader('HX-Redirect');

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

        $this->delete(
            route('scripts.delete', [
                'script' => $script,
            ])
        )->assertRedirect();

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

        $this->post(
            route('scripts.execute', [
                'script' => $script,
            ]),
            [
                'server' => $this->server->id,
                'user' => 'root',
            ]
        )
            ->assertSessionDoesntHaveErrors()
            ->assertHeader('HX-Redirect');

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
        ]);

        $execution = $script->lastExecution;

        $this->get(
            route('scripts.log', [
                'script' => $script,
                'execution' => $execution,
            ])
        )
            ->assertRedirect()
            ->assertSessionHas('content', 'script output');
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

        $this->get(
            route('scripts.show', [
                'script' => $script,
            ])
        )
            ->assertSuccessful()
            ->assertSee($scriptExecution->status);
    }
}
