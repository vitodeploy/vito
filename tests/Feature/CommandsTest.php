<?php

use App\Enums\UserRole;
use App\Facades\SSH;
use App\Jobs\Site\ExecuteCommandJob;
use App\Models\Command;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see commands', function () {
    $this->actingAs($this->user);

    $this->get(route('commands', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('commands/index'));
});

test('create command', function () {
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
});

test('edit command', function () {
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
});

test('delete command', function () {
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
});

test('execute command', function (UserRole $role): void {
    SSH::fake('echo "Hello, world!"');

    $this->user->update(['is_admin' => false]);
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => $role,
    ]);

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
        ->assertRedirect(route('commands.show', [
            'server' => $this->server,
            'site' => $this->site,
            'command' => $command,
        ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('command_executions', [
        'command_id' => $command->id,
        'variables' => $this->castAsJson(['MESSAGE' => 'Hello, world!']),
    ]);
})->with([UserRole::OWNER, UserRole::ADMIN]);

test('cannot execute a command belonging to another site', function (bool $differentProject): void {
    SSH::fake();
    Queue::fake();

    $server = $differentProject ? Server::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'user_id' => $this->user->id,
    ]) : $this->server;
    $site = Site::factory()->create(['server_id' => $server->id]);
    $command = $site->commands()->create([
        'name' => 'Other Site Command',
        'command' => 'echo "Hello, world!"',
    ]);

    $this->actingAs($this->user);

    $this->post(route('commands.execute', [
        'server' => $this->server,
        'site' => $this->site,
        'command' => $command,
    ]))
        ->assertForbidden();

    $this->assertDatabaseMissing('command_executions', ['command_id' => $command->id]);
    Queue::assertNotPushed(ExecuteCommandJob::class);
})->with([
    'same server' => false,
    'another project' => true,
]);

test('cannot execute commands without project write access', function (?UserRole $role): void {
    SSH::fake();
    Queue::fake();

    $user = User::factory()->create(['is_admin' => false]);
    $user->ensureHasDefaultProject();

    if ($role !== null) {
        $this->server->project->users()->create([
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    $command = $this->site->commands()->create([
        'name' => 'Test Command',
        'command' => 'echo "Hello, world!"',
    ]);

    $this->actingAs($user);

    $this->post(route('commands.execute', [
        'server' => $this->server,
        'site' => $this->site,
        'command' => $command,
    ]))
        ->assertForbidden();

    $this->assertDatabaseMissing('command_executions', ['command_id' => $command->id]);
    Queue::assertNotPushed(ExecuteCommandJob::class);
})->with([
    'non-member' => null,
    'read-only member' => UserRole::USER,
]);

test('command routes reject a site belonging to another server', function (string $method, string $route): void {
    SSH::fake();
    Queue::fake();

    $server = Server::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'user_id' => $this->user->id,
    ]);
    $site = Site::factory()->create(['server_id' => $server->id]);
    $command = $site->commands()->create([
        'name' => 'Other Site Command',
        'command' => 'echo "Hello, world!"',
    ]);

    $this->actingAs($this->user);

    $this->{$method}(route($route, [
        'server' => $this->server,
        'site' => $site,
        'command' => $command,
    ]), [
        'name' => 'Unauthorized Command',
        'command' => 'whoami',
    ])
        ->assertForbidden();

    $this->assertDatabaseMissing('commands', ['name' => 'Unauthorized Command']);
    $this->assertDatabaseHas('commands', [
        'id' => $command->id,
        'name' => 'Other Site Command',
        'command' => 'echo "Hello, world!"',
    ]);
    $this->assertDatabaseMissing('command_executions', ['command_id' => $command->id]);
    Queue::assertNotPushed(ExecuteCommandJob::class);
})->with([
    'list' => ['get', 'commands'],
    'create' => ['post', 'commands.store'],
    'show' => ['get', 'commands.show'],
    'update' => ['put', 'commands.update'],
    'delete' => ['delete', 'commands.destroy'],
    'execute' => ['post', 'commands.execute'],
]);

test('execute command validation error', function () {
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
});
