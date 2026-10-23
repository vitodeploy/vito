<?php

use App\Actions\Component\CreateComponent;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\GetComponentOptions;
use App\Actions\Environment\UpdateEnvironment;
use App\ComponentCapabilities\Web;
use App\DTOs\DynamicForm;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\Component\DeleteComponentJob;
use App\Jobs\Component\InstallComponentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\Project;
use App\Models\Server;
use App\Plugins\RegisterComponentCapability;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Http::preventStrayRequests();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'source_control_id' => $this->site->source_control_id]);
    $this->environment = Environment::factory()->for($this->application)->create(['runtime_version' => '22']);
    $this->input = ['name' => 'Web', 'server_id' => $this->server->id, 'user' => 'runtime', 'type' => 'app', 'capabilities' => [['capability' => 'web', 'config' => []]]];
});

test('components create atomically with normalized capabilities and a first primary', function (bool $defaultUser) {
    $input = array_replace($this->input, ['user' => $defaultUser ? $this->server->getSshUser() : 'runtime', 'path' => '/etc', 'status' => 'ready']);
    $component = app(CreateComponent::class)->create($this->environment, $input);

    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'pending', 'user' => $input['user'], 'path' => '/home/'.$input['user'].'/app-'.$this->application->id.'-'.$this->environment->slug]);
    $this->assertDatabaseHas('component_capabilities', ['component_id' => $component->id, 'capability' => 'web', 'config->port' => 10000, 'status' => 'pending']);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'primary_component_id' => $component->id, 'primary_component_initialized' => true]);
    expect($component->isolated_user_id === null)->toBe($defaultUser);
    Queue::assertPushed(InstallComponentJob::class);
    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([false, true]);

test('components reject invalid placement and capability input without partial rows', function (string $field, mixed $value) {
    $input = $this->input;
    data_set($input, $field, $value);

    expect(fn () => app(CreateComponent::class)->create($this->environment, $input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('components', 0);
    $this->assertDatabaseCount('component_capabilities', 0);
    $this->assertDatabaseMissing('isolated_users', ['username' => 'runtime']);
    Queue::assertNothingPushed();
})->with([
    ['name', ''], ['name', []], ['name', str_repeat('a', 256)], ['server_id', 999999], ['server_id', []],
    ['user', 'root'], ['user', 'ab'], ['user', 'unsafe;id'], ['user', "runtime\n"], ['user', []],
    ['type', 'load_balancer'], ['type', 'other'], ['capabilities', 'web'], ['capabilities', ['web' => []]],
    ['capabilities.0.capability', 'unknown'], ['capabilities.0.config', 'invalid'], ['capabilities.0.config.port', 80],
    ['capabilities.0.config.start_command', "bad\0command"],
]);

test('components reject foreign and unready servers', function (bool $foreign) {
    $server = $foreign ? Server::factory()->create() : $this->server;
    if (! $foreign) {
        $server->update(['status' => ServerStatus::INSTALLING]);
    }
    expect(fn () => app(CreateComponent::class)->create($this->environment, array_replace($this->input, ['server_id' => $server->id])))
        ->toThrow(ValidationException::class);
})->with([false, true]);

test('component placement names users and web ownership are unique', function (string $kind) {
    app(CreateComponent::class)->create($this->environment, $this->input);
    $input = array_replace($this->input, ['name' => 'Another', 'user' => 'another']);
    if ($kind === 'name') {
        $input['name'] = $this->input['name'];
        $input['capabilities'] = [];
    } elseif ($kind === 'user') {
        $input['user'] = $this->input['user'];
        $input['capabilities'] = [];
    }
    expect(fn () => app(CreateComponent::class)->create($this->environment, $input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('components', 1);
})->with(['name', 'user', 'web']);

test('component creation checks preset services even without capabilities', function (string $status) {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $this->server->services()->where('type', 'php')->update(['status' => $status]);
    $input = array_replace($this->input, ['capabilities' => []]);
    if ($status === ServiceStatus::INSTALLING->value) {
        expect(fn () => app(CreateComponent::class)->create($this->environment->fresh(), $input))->toThrow(ValidationException::class);
    } else {
        expect(app(CreateComponent::class)->create($this->environment->fresh(), $input))->toBeInstanceOf(Component::class);
    }
})->with(['ready', 'stopped', 'disabled', 'installing']);

test('component validation rejects missing php versions and capability services', function (bool $php) {
    if ($php) {
        $this->application->update(['runtime' => 'php']);
        $this->environment->update(['runtime_version' => '0.0']);
    } else {
        $this->server->services()->where('type', 'webserver')->delete();
    }
    expect(fn () => app(CreateComponent::class)->create($this->environment->fresh(), $this->input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('components', 0);
})->with([false, true]);

test('primary selection is never automatically reassigned after the first component', function () {
    $first = app(CreateComponent::class)->create($this->environment, $this->input);
    app(DeleteComponent::class)->delete($first, []);
    $first->delete();
    $next = app(CreateComponent::class)->create($this->environment, $this->input);
    expect($this->environment->fresh()->primary_component_id)->toBeNull();
    $input = ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '22', 'primary_component_id' => $next->id];
    app(UpdateEnvironment::class)->update($this->environment, $input);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'primary_component_id' => $next->id]);
});

test('environment runtime is locked once components exist but primary can be cleared', function () {
    app(CreateComponent::class)->create($this->environment, $this->input);
    $input = ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '24'];
    expect(fn () => app(UpdateEnvironment::class)->update($this->environment, $input))->toThrow(ValidationException::class);
    app(UpdateEnvironment::class)->update($this->environment, array_replace($input, ['runtime_version' => '22', 'primary_component_id' => null]));
    expect($this->environment->fresh()->primary_component_id)->toBeNull();
});

test('primary selection rejects other environments load balancers and deleting components', function (string $kind) {
    $component = Component::factory()->for($this->environment)->for($this->server)->create();
    if ($kind === 'foreign') {
        $component->update(['environment_id' => Environment::factory()->create()->id]);
    } elseif ($kind === 'lb') {
        $component->update(['type' => ComponentType::LOAD_BALANCER]);
    } else {
        $component->status = ComponentStatus::DELETING;
        $component->save();
    }
    $input = ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '22', 'primary_component_id' => $component->id];
    expect(fn () => app(UpdateEnvironment::class)->update($this->environment, $input))->toThrow(ValidationException::class);
})->with(['foreign', 'lb', 'deleting']);

test('component endpoints create show retry and queue deletion with scoped resource fields', function () {
    $this->actingAs($this->user);
    $parents = [$this->application, $this->environment];
    $response = $this->post(route('components.store', $parents), $this->input)->assertSessionDoesntHaveErrors();
    $component = $this->environment->components()->sole();
    $response->assertRedirect(route('environments.show', [...$parents, 'component' => $component->id]));
    $this->get(route('components.show', [...$parents, $component]))->assertRedirect(route('environments.show', [...$parents, 'component' => $component->id]));
    $this->get(route('environments.show', [...$parents, 'component' => $component->id]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('applications/environments/show')
            ->where('component.id', $component->id)->where('component.status', 'pending')->where('component.status_color', 'gray')
            ->where('component.server_name', $this->server->name)->where('component.capabilities', ['web'])
            ->where('environment.primary_component_id', $component->id)->where('environment.components_count', 1)
            ->missing('component.deploy_key_id')->missing('component.deploy_key_source_control_id')->missing('component.server')
            ->where('can.updateComponent', true)->where('can.deleteComponent', true));
    $component->forceFill(['status' => ComponentStatus::INSTALLATION_FAILED])->save();
    $this->post(route('components.retry', [...$parents, $component]))->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'pending']);
    $this->delete(route('components.destroy', [...$parents, $component]))->assertRedirect(route('environments.show', $parents));
    Queue::assertPushed(DeleteComponentJob::class);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'deleting']);
});

test('component listing scopes tables ready servers and dynamic capability forms', function () {
    $this->actingAs($this->user);
    $component = app(CreateComponent::class)->create($this->environment, $this->input);
    Component::factory()->create();
    Server::factory()->create(['project_id' => $this->server->project_id, 'status' => ServerStatus::INSTALLING]);
    $this->get(route('environments.show', [$this->application, $this->environment]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('applications/environments/show')
            ->has('topology.nodes', 1)->where('topology.nodes.0.record_id', $component->id)->missing('options')->where('capabilities', [])
            ->where('can.createComponent', true)->where('component', null)->where('can.updateComponent', false)->where('can.deleteComponent', false)
            ->reloadOnly('options', fn (AssertableInertia $reload) => $reload
                ->has('options.servers', 1)->where('options.servers.0.id', $this->server->id)
                ->has('options.capabilities', 3)->where('options.capabilities.0.id', 'web')
                ->where('options.capabilities.0.form.0.name', 'web_directory')));
    $this->get(route('environments.show', [$this->application, $this->environment, 'component' => $component->id]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('capabilities', 3)->where('capabilities.0.id', 'web')->missing('options'));
});

test('component routes reject mismatched parents before exposing data', function () {
    $this->actingAs($this->user);
    $component = Component::factory()->for($this->environment)->for($this->server)->create();
    $other = Environment::factory()->for($this->application)->create();
    $parents = [$this->application, $other, $component];
    $this->getJson(route('components.show', $parents))->assertNotFound();
    $this->postJson(route('components.retry', $parents))->assertNotFound();
    $this->deleteJson(route('components.destroy', $parents))->assertNotFound();
    $otherApplication = Application::factory()->create();
    $this->getJson(route('environments.show', [$otherApplication, $this->environment, 'component' => $component->id]))->assertNotFound();
    $this->postJson(route('components.store', [$otherApplication, $this->environment]), $this->input)->assertNotFound();
});

test('component panel only resolves components of the routed environment', function () {
    $this->actingAs($this->user);
    $own = Component::factory()->for($this->environment)->for($this->server)->create(['name' => 'own-component']);
    $sibling = Environment::factory()->for($this->application)->create();
    $foreign = Component::factory()->for($sibling)->for($this->server)->create(['name' => 'sibling-secret-component', 'path' => '/home/vito/sibling-secret-path']);
    $foreign->deploymentScripts()->create(['name' => 'deploy', 'content' => 'echo sibling-secret-script']);
    $parents = [$this->application, $this->environment];

    $this->get(route('environments.show', [...$parents, 'component' => $foreign->id]))->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use ($own) {
            $page->where('component', null)->where('componentDomains', [])->where('componentScripts', [])->where('inheritedScripts', [])
                ->where('workers', null)->where('cronjobs', null)->where('backendTargets', [])
                ->where('can.updateComponent', false)->where('can.deleteComponent', false)
                ->has('topology.nodes', 1)->where('topology.nodes.0.record_id', $own->id);
            expect(json_encode($page->toArray()['props']))->not->toContain('sibling-secret');
        });
    $this->get(route('environments.show', [$this->application, $sibling, 'component' => $own->id]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('component', null)->where('can.updateComponent', false));
    $this->get(route('environments.show', [...$parents, 'component' => 'not-a-number']))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('component', null));
    $outside = Component::factory()->for($this->environment)->for(Server::factory()->create(['name' => 'outside-secret-server', 'project_id' => Project::factory()->create()->id]))->create(['name' => 'outside-secret-component']);
    $this->get(route('environments.show', [...$parents, 'component' => $outside->id]))->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) {
            $page->where('component', null)->where('capabilities', [])->where('domainOptions', null)->where('can.updateComponent', false);
            expect(json_encode($page->toArray()['props']))->not->toContain('outside-secret');
        });
});

test('component endpoints enforce project membership authentication and write access', function (string $access) {
    $this->actingAs($this->user);
    $component = Component::factory()->for($this->environment)->for($this->server)->create();
    if ($access === 'guest') {
        auth()->logout();
    } elseif ($access === 'foreign') {
        $this->application->update(['project_id' => Project::factory()->create()->id]);
    } else {
        $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    }
    $parents = [$this->application, $this->environment];
    $status = $access === 'guest' ? 401 : 403;
    if ($access === 'reader') {
        $this->get(route('environments.show', $parents))->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can.createComponent', false)
                ->reloadOnly(['options', 'resourceOptions'], fn (AssertableInertia $reload) => $reload->where('options', null)->where('resourceOptions', null)));
        $this->get(route('components.show', [...$parents, $component]))->assertRedirect(route('environments.show', [...$parents, 'component' => $component->id]));
        $this->get(route('environments.show', [...$parents, 'component' => $component->id]))->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('component.id', $component->id)
                ->where('can.updateComponent', false)->where('can.deleteComponent', false));
    } else {
        $this->getJson(route('environments.show', $parents))->assertStatus($status);
        $this->getJson(route('environments.show', [...$parents, 'component' => $component->id]))->assertStatus($status);
        $this->getJson(route('components.show', [...$parents, $component]))->assertStatus($status);
    }
    $this->postJson(route('components.store', $parents), $this->input)->assertStatus($status);
    $this->postJson(route('components.retry', [...$parents, $component]))->assertStatus($status);
    $this->deleteJson(route('components.destroy', [...$parents, $component]))->assertStatus($status);
    Queue::assertNothingPushed();
})->with(['guest', 'foreign', 'reader']);

test('component attachment holds the same isolated user lock as site teardown', function () {
    Event::listen('eloquent.creating: '.Component::class, function (): void {
        expect($this->server->isolatedUserLock('runtime')->get())->toBeFalse();
        expect(Cache::lock('unique-queue:server-'.$this->server->id, 600)->get())->toBeFalse();
    });

    app(CreateComponent::class)->create($this->environment, $this->input);
    expect($this->server->isolatedUserLock('runtime')->get(fn () => true))->toBeTrue();
});

test('busy component servers return validation errors without attaching rows', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(10, Mockery::type(Closure::class))->andThrow(new LockTimeoutException);
    Cache::shouldReceive('lock')->once()->with('unique-queue:server-'.$this->server->id, 600)->andReturn($lock);

    try {
        app(CreateComponent::class)->create($this->environment, $this->input);
        $this->fail('Expected a busy server validation error.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('server_id');
    }
    $this->assertDatabaseCount('components', 0);
    Queue::assertNothingPushed();
});

test('component options honor registered capability labels and explicit forms', function () {
    RegisterComponentCapability::make('web')->handler(Web::class)
        ->label('Custom web')->form(new DynamicForm)->register();

    $options = app(GetComponentOptions::class)->get($this->environment);
    expect($options['capabilities'][0]['label'])->toBe('Custom web')
        ->and($options['capabilities'][0]['form'])->toBe([]);
});
