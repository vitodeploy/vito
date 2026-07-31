<?php

use App\Enums\ScriptExecutionStatus;
use App\Facades\SSH;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see scripts', function () {
    $this->actingAs($this->user);

    Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->get(route('scripts'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('scripts/index'));
});

test('create script', function () {
    $this->actingAs($this->user);

    $this->post(route('scripts.store'), [
        'name' => 'Test Script',
        'content' => 'echo "Hello, World!"',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('scripts', [
        'name' => 'Test Script',
        'content' => 'echo "Hello, World!"',
    ]);
});

test('edit script', function () {
    $this->actingAs($this->user);

    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->put(route('scripts.update', $script), [
        'name' => 'New Name',
        'content' => 'echo "Hello, new World!"',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('scripts', [
        'id' => $script->id,
        'name' => 'New Name',
        'content' => 'echo "Hello, new World!"',
    ]);
});

test('delete script', function () {
    $this->actingAs($this->user);

    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $scriptExecution = ScriptExecution::factory()->create([
        'script_id' => $script->id,
        'status' => ScriptExecutionStatus::EXECUTING,
    ]);

    $this->delete(route('scripts.destroy', $script->id));

    $this->assertDatabaseMissing('scripts', [
        'id' => $script->id,
    ]);

    $this->assertDatabaseMissing('script_executions', [
        'id' => $scriptExecution->id,
    ]);
});

test('execute script and view log', function () {
    SSH::fake('script output');

    $this->actingAs($this->user);

    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->post(route('scripts.execute', $script), [
        'server' => $this->server->id,
        'user' => 'root',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('script_executions', [
        'script_id' => $script->id,
        'status' => ScriptExecutionStatus::COMPLETED,
        'user' => 'root',
    ]);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
    ]);

    $this->get(route('scripts.show', $script))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('scripts/show'));
});

test('execute script as isolated user', function () {
    SSH::fake('script output');

    $this->actingAs($this->user);

    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'example',
    ]);

    $this->post(route('scripts.execute', $script), [
        'server' => $this->server->id,
        'user' => 'example',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('script_executions', [
        'script_id' => $script->id,
        'status' => ScriptExecutionStatus::COMPLETED,
        'user' => 'example',
    ]);
});

test('cannot execute script as non existing user', function () {
    $this->actingAs($this->user);

    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->post(route('scripts.execute', $script), [
        'server' => $this->server->id,
        'user' => 'example',
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('script_executions', [
        'script_id' => $script->id,
        'user' => 'example',
    ]);
});

test('cannot execute script as user not on server', function () {
    $this->actingAs($this->user);

    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Site::factory()->create([
        'server_id' => Server::factory()->create(['user_id' => 1])->id,
        'user' => 'example',
    ]);

    $this->post(route('scripts.execute', $script), [
        'server' => $this->server->id,
        'user' => 'example',
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('script_executions', [
        'script_id' => $script->id,
        'user' => 'example',
    ]);
});
