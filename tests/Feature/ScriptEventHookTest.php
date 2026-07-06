<?php

namespace Tests\Feature;

use App\Enums\ScriptEventHookEvent;
use App\Enums\ScriptExecutionStatus;
use App\Events\ServerDeletedEvent;
use App\Events\ServerInstalledEvent;
use App\Events\ServiceInstalledEvent;
use App\Events\ServiceUninstalledEvent;
use App\Events\SiteCreatedEvent;
use App\Events\SiteDeletedEvent;
use App\Facades\SSH;
use App\Models\Script;
use App\Models\ScriptEventHook;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptEventHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_hooks_requires_auth(): void
    {
        $script = Script::factory()->create(['user_id' => $this->user->id]);

        $this->get(route('scripts.hooks', ['script' => $script->id]))
            ->assertRedirect();
    }

    public function test_other_user_cannot_see_hooks(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $this->actingAs($otherUser);

        $script = Script::factory()->create(['user_id' => $this->user->id]);

        $this->get(route('scripts.hooks', ['script' => $script->id]))
            ->assertForbidden();
    }

    public function test_create_hook(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('scripts.hooks.store', ['script' => $script->id]), [
            'event' => ScriptEventHookEvent::SITE_CREATED->value,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'user' => 'root',
            'enabled' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('script_event_hooks', [
            'script_id' => $script->id,
            'event' => ScriptEventHookEvent::SITE_CREATED->value,
            'server_id' => $this->server->id,
            'user' => 'root',
            'enabled' => true,
        ]);
    }

    public function test_update_hook(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create(['user_id' => $this->user->id]);
        $hook = ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
        ]);

        $this->put(route('scripts.hooks.update', ['script' => $script->id, 'hook' => $hook->id]), [
            'event' => ScriptEventHookEvent::SITE_DELETED->value,
            'server_id' => $this->server->id,
            'user' => 'root',
            'enabled' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('script_event_hooks', [
            'id' => $hook->id,
            'event' => ScriptEventHookEvent::SITE_DELETED->value,
            'enabled' => false,
        ]);
    }

    public function test_delete_hook(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create(['user_id' => $this->user->id]);
        $hook = ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('scripts.hooks.destroy', ['script' => $script->id, 'hook' => $hook->id]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('script_event_hooks', ['id' => $hook->id]);
    }

    public function test_other_user_cannot_delete_hook(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $this->actingAs($otherUser);

        $script = Script::factory()->create(['user_id' => $this->user->id]);
        $hook = ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('scripts.hooks.destroy', ['script' => $script->id, 'hook' => $hook->id]))
            ->assertForbidden();
    }

    public function test_site_created_event_triggers_hook(): void
    {
        SSH::fake();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "site created"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SITE_CREATED,
        ]);

        SiteCreatedEvent::dispatch($this->site);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'server_id' => $this->server->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);
    }

    public function test_site_deleted_event_triggers_hook(): void
    {
        SSH::fake();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "site deleted"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SITE_DELETED,
        ]);

        SiteDeletedEvent::dispatch($this->server, $this->site->id, $this->site->domain);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);
    }

    public function test_server_installed_event_triggers_hook(): void
    {
        SSH::fake();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "server installed"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SERVER_INSTALLED,
        ]);

        ServerInstalledEvent::dispatch($this->server);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);
    }

    public function test_server_deleted_event_triggers_hook(): void
    {
        SSH::fake();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "server deleted"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SERVER_DELETED,
        ]);

        ServerDeletedEvent::dispatch($this->server->id, $this->server->name, $this->server->ip, $this->server->project_id);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);
    }

    public function test_service_installed_event_triggers_hook(): void
    {
        SSH::fake();

        /** @var Service $service */
        $service = $this->server->services()->first();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "service installed"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SERVICE_INSTALLED,
        ]);

        ServiceInstalledEvent::dispatch($service);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);
    }

    public function test_service_uninstalled_event_triggers_hook(): void
    {
        SSH::fake();

        /** @var Service $service */
        $service = $this->server->services()->first();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "service uninstalled"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SERVICE_UNINSTALLED,
        ]);

        ServiceUninstalledEvent::dispatch($service->id, $service->name, $service->type, $service->server_id, $service->server->project_id);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);
    }

    public function test_disabled_hook_is_not_executed(): void
    {
        SSH::fake();

        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "test"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SITE_CREATED,
            'enabled' => false,
        ]);

        SiteCreatedEvent::dispatch($this->site);

        $this->assertDatabaseMissing('script_executions', ['script_id' => $script->id]);
    }

    public function test_hook_for_different_project_is_not_triggered(): void
    {
        SSH::fake();

        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();

        $script = Script::factory()->create(['user_id' => $otherUser->id, 'content' => 'echo "test"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $otherUser->id,
            'project_id' => $otherUser->current_project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SITE_CREATED,
        ]);

        SiteCreatedEvent::dispatch($this->site);

        $this->assertDatabaseMissing('script_executions', ['script_id' => $script->id]);
    }

    public function test_event_variables_are_injected_into_script(): void
    {
        SSH::fake();

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'echo ${site_domain}',
        ]);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SITE_CREATED,
        ]);

        SiteCreatedEvent::dispatch($this->site);

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
        ]);

        $execution = \App\Models\ScriptExecution::query()
            ->where('script_id', $script->id)
            ->firstOrFail();

        $this->assertEquals($this->site->domain, $execution->variables['site_domain']);
    }

    public function test_hook_exception_does_not_propagate(): void
    {
        $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo "test"']);
        ScriptEventHook::factory()->create([
            'script_id' => $script->id,
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'server_id' => $this->server->id,
            'event' => ScriptEventHookEvent::SITE_CREATED,
        ]);

        // Make executeForHook throw by binding a mock that always throws
        $this->mock(\App\Actions\Script\ExecuteScript::class, function ($mock): void {
            $mock->shouldReceive('executeForHook')->andThrow(new \RuntimeException('simulated failure'));
        });

        // Should not throw — listener catches and logs errors per hook
        SiteCreatedEvent::dispatch($this->site);

        $this->assertTrue(true);
    }
}
