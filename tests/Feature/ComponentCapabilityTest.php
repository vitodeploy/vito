<?php

use App\ComponentCapabilities\Scheduler;
use App\ComponentCapabilities\Web;
use App\ComponentCapabilities\Worker;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\Server;
use App\Models\Site;
use App\RuntimePresets\Custom;
use App\RuntimePresets\Laravel;
use App\RuntimePresets\NodeJS;
use App\RuntimePresets\PHP;
use App\RuntimePresets\StaticSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Http::fake();
    $application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'node']);
    $environment = Environment::factory()->for($application)->create(['runtime_version' => '24']);
    $this->component = Component::factory()->for($environment)->for($this->server)->create();
});

function componentCapabilityForTest(Component $component, string $id = 'web', ?array $config = null): ComponentCapability
{
    return new ComponentCapability(['component_id' => $component->id, 'capability' => $id, 'config' => $config]);
}

function assertCapabilityValidationError(Closure $callback, string $field): void
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        expect($exception->status)->toBe(422)
            ->and($exception->errors())->toHaveKey($field);

        return;
    }

    test()->fail('Expected a capability validation error for '.$field);
}

test('built-in capabilities are registered with surfaces and runtime-aware forms', function () {
    $capabilities = $this->actingAs($this->user)->getJson('/bootstrap')->assertSuccessful()->json('configs.application.capabilities');

    expect(array_column($capabilities, 'id'))->toBe(['web', 'worker', 'scheduler']);

    foreach (['web' => ['domains'], 'worker' => ['workers'], 'scheduler' => ['cron_jobs']] as $id => $surfaces) {
        $handler = componentCapabilityForTest($this->component, $id)->handler();
        expect(collect($capabilities)->firstWhere('id', $id))->toBe([
            'id' => $id, 'label' => $handler::label(), 'description' => $handler->description(), 'surfaces' => $surfaces,
        ]);
    }

    $web = new Web(new ComponentCapability);
    expect($web->form(new NodeJS)->getFieldNames())->toBe(['web_directory', 'start_command', 'port'])
        ->and($web->form(new StaticSite)->getFieldNames())->toBe(['web_directory'])
        ->and($web->form(new Laravel)->toArray()[0]['default'])->toBe('public')
        ->and($web->form(new NodeJS)->toArray()[1]['default'])->toBe('npm start')
        ->and((new Worker(new ComponentCapability))->form(new Custom)->toArray())->toBe([])
        ->and((new Scheduler(new ComponentCapability))->form(new Laravel)->toArray()[0]['default'])->toBe('php artisan schedule:run');
});

test('web service requirements follow the web mode', function () {
    $web = new Web(new ComponentCapability);

    expect($web->requiredServices(new PHP))->toBe(['webserver', 'php'])
        ->and($web->requiredServices(new NodeJS))->toBe(['webserver', 'process_manager'])
        ->and($web->requiredServices(new StaticSite))->toBe(['webserver']);
});

test('capabilities reject missing or uninstalled services', function (string $capability, string $runtime, string $service, ?ServiceStatus $status) {
    $this->component->environment->application->update(['runtime' => $runtime]);
    $query = $this->server->services()->where('type', $service);
    if ($status === null) {
        $query->delete();
    } else {
        $query->update(['status' => $status]);
    }
    $record = componentCapabilityForTest($this->component, $capability);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'capabilities');
})->with([
    ['web', 'node', 'webserver', null],
    ['web', 'static', 'webserver', null],
    ['web', 'php', 'php', null],
    ['web', 'node', 'process_manager', null],
    ['worker', 'node', 'process_manager', null],
    ['worker', 'node', 'process_manager', ServiceStatus::INSTALLING],
    ['web', 'node', 'webserver', ServiceStatus::INSTALLATION_FAILED],
]);

test('capability validation does not change installed service defaults', function () {
    $this->server->services()->update(['is_default' => false, 'status' => ServiceStatus::STOPPED]);
    $record = componentCapabilityForTest($this->component, 'worker');
    $record->handler()->validate($this->component, $this->server);

    $this->assertDatabaseMissing('services', ['server_id' => $this->server->id, 'is_default' => true]);
    $this->assertDatabaseCount('component_capabilities', 0);
});

test('fpm web requires the exact environment PHP version', function (?string $version) {
    $this->component->environment->application->update(['runtime' => 'php']);
    $this->component->environment->update(['runtime_version' => $version]);
    $record = componentCapabilityForTest($this->component);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'runtime_version');
})->with(['8.4', null, '']);

test('fpm and static web preserve editable directory suggestions without proxy config', function (string $runtime, ?string $directory) {
    $this->component->environment->application->update(['runtime' => $runtime]);
    $this->component->environment->update(['runtime_version' => '8.2']);
    $record = componentCapabilityForTest($this->component);
    $record->handler()->validate($this->component, $this->server);

    expect($record->config)->toBe(['web_directory' => $directory]);

    $record->config = ['web_directory' => 'dist'];
    $record->handler()->validate($this->component, $this->server);
    expect($record->config)->toBe(['web_directory' => 'dist']);

    $record->config = ['web_directory' => null];
    $record->handler()->validate($this->component, $this->server);
    expect($record->config)->toBe(['web_directory' => null]);
})->with([['php', null], ['laravel', 'public'], ['static', null]]);

test('web directories cannot escape the release or inject shell syntax', function (string $directory) {
    $record = componentCapabilityForTest($this->component, config: ['web_directory' => $directory]);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'web_directory');
})->with(['../shared', 'public/../../etc', '/etc', '..', 'public\\..\\etc', "public\n", "public\0", 'public;id', '$(id)', 'public/..']);

test('relative web directories are accepted', function (?string $directory) {
    $record = componentCapabilityForTest($this->component, config: ['web_directory' => $directory]);
    $record->handler()->validate($this->component, $this->server);

    expect($record->config['web_directory'])->toBe($directory);
})->with([null, '', 'public', 'dist/assets', '.output/public', 'www-root/']);

test('proxy web requires a start command when the preset has none', function () {
    $this->component->environment->application->update(['runtime' => 'custom']);
    $record = componentCapabilityForTest($this->component);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'start_command');

    $record->config = ['start_command' => './server --port "$PORT"'];
    $record->handler()->validate($this->component, $this->server);
    expect($record->config['start_command'])->toBe('./server --port "$PORT"');
});

test('explicitly empty proxy commands are not silently replaced by suggestions', function (?string $command) {
    $record = componentCapabilityForTest($this->component, config: ['start_command' => $command]);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'start_command');
})->with([null, '', '   ']);

test('proxy ports skip component and Site reservations without persisting validation', function ($empty) {
    config(['component.port_range' => ['min' => 12000, 'max' => 12002]]);
    $this->site->update(['type' => 'node', 'port' => 12000]);
    $other = Component::factory()->for($this->server)->create();
    ComponentCapability::factory()->for($other)->create(['config' => ['port' => '12001']]);
    $record = componentCapabilityForTest($this->component, config: ['port' => $empty]);
    $record->handler()->validate($this->component, $this->server);

    expect($record->config['port'])->toBe(12002)
        ->and($record->config['start_command'])->toBe('npm start')
        ->and($record->exists)->toBeFalse();
    $this->assertDatabaseCount('component_capabilities', 1);
})->with([null, '']);

test('explicit ports cannot conflict with another component or Site', function (string $owner) {
    if ($owner === 'component') {
        $other = Component::factory()->for($this->server)->create();
        ComponentCapability::factory()->for($other)->create(['config' => ['port' => 13000]]);
    } else {
        $this->site->update(['type' => 'node', 'port' => 13000]);
    }
    $record = componentCapabilityForTest($this->component, config: ['port' => '13000']);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'port');
})->with(['component', 'site']);

test('proxy ports are server scoped and an existing capability may keep or edit its port', function () {
    $other = Component::factory()->create();
    ComponentCapability::factory()->for($other)->create(['config' => ['port' => 13000]]);
    Site::factory()->for($other->server)->create(['port' => 13000]);
    $record = ComponentCapability::factory()->for($this->component)->create(['config' => ['port' => 13000]]);
    $record->handler()->validate($this->component, $this->server);

    expect($record->config['port'])->toBe(13000);

    $record->config = ['start_command' => 'node server.js', 'port' => '14000'];
    $record->handler()->validate($this->component, $this->server);
    expect($record->config['port'])->toBe(14000);
    $this->assertDatabaseHas('component_capabilities', ['id' => $record->id, 'config->port' => 13000]);
});

test('proxy ports must be non-privileged integers', function ($port) {
    $record = componentCapabilityForTest($this->component, config: ['port' => $port]);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'port');
})->with([0, 80, 1023, 65536, -1, 'not-a-port', 3000.5, [[3000]]]);

test('proxy port exhaustion returns a validation error', function () {
    config(['component.port_range' => ['min' => 12000, 'max' => 12000]]);
    $this->site->update(['port' => 12000]);
    $record = componentCapabilityForTest($this->component);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'port');
});

test('non-proxy web rejects proxy-only configuration', function (array $config) {
    $this->component->environment->application->update(['runtime' => 'static']);
    $record = componentCapabilityForTest($this->component, config: $config);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), array_key_first($config));
})->with([[['port' => 12000]], [['start_command' => 'run']]]);

test('scheduler uses preset suggestions and permits arbitrary editable commands', function () {
    $this->component->environment->application->update(['runtime' => 'laravel']);
    $record = componentCapabilityForTest($this->component, 'scheduler');
    $record->handler()->validate($this->component, $this->server);
    expect($record->config)->toBe(['command' => 'php artisan schedule:run']);

    $this->component->environment->application->update(['runtime' => 'node']);
    $record->config = ['command' => 'node tasks.js'];
    $record->handler()->validate($this->component, $this->server);
    expect($record->config)->toBe(['command' => 'node tasks.js']);
    $this->assertDatabaseCount('component_capabilities', 0);
});

test('scheduler needs a command for presets without a suggestion', function (?array $config) {
    $record = componentCapabilityForTest($this->component, 'scheduler', $config);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'command');
})->with([null, [['command' => '']], [['command' => null]], [['command' => '   ']]]);

test('a second scheduler in an environment is rejected even on another server', function () {
    $other = Component::factory()->for($this->component->environment)->create();
    ComponentCapability::factory()->for($other)->create(['capability' => 'scheduler', 'config' => ['command' => 'run']]);
    $record = componentCapabilityForTest($this->component, 'scheduler', ['command' => 'node tasks.js']);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'capabilities');
});

test('scheduler uniqueness excludes itself and does not cross environments', function () {
    ComponentCapability::factory()->create(['capability' => 'scheduler']);
    $record = ComponentCapability::factory()->for($this->component)->create(['capability' => 'scheduler', 'config' => ['command' => 'node tasks.js']]);
    $record->handler()->validate($this->component, $this->server);

    expect($record->config['command'])->toBe('node tasks.js');
});

test('capability validation rejects mismatched parents', function (string $mismatch) {
    $record = componentCapabilityForTest($this->component, 'worker');
    if ($mismatch === 'component') {
        $record->component_id = $this->component->id + 1;
        $server = $this->server;
    } else {
        $server = Server::factory()->create();
    }

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $server), 'capabilities');
})->with(['component', 'server']);

test('installed services satisfy capability requirements without changing their state', function (string $capability, string $runtime, ServiceStatus $status) {
    $this->component->environment->application->update(['runtime' => $runtime]);
    $this->component->environment->update(['runtime_version' => '8.2']);
    $this->server->services()->update(['status' => $status]);
    $record = componentCapabilityForTest($this->component, $capability);

    $record->handler()->validate($this->component, $this->server);

    expect($this->server->services()->where('status', '!=', $status)->exists())->toBeFalse();
    $this->assertDatabaseCount('component_capabilities', 0);
})->with([['web', 'php'], ['web', 'node'], ['worker', 'node']])
    ->with([ServiceStatus::READY, ServiceStatus::STOPPED, ServiceStatus::DISABLED]);

test('capability validation rejects scalar configuration without changing it', function (string $capability, mixed $config) {
    $record = componentCapabilityForTest($this->component, $capability);
    $record->config = $config;

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'config');

    expect($record->config)->toBe($config);
    $this->assertDatabaseCount('component_capabilities', 0);
})->with(['web', 'worker', 'scheduler'])->with(['not-an-array', true, false, 123, '']);

test('capability commands reject NUL bytes while preserving editable shell syntax', function (string $capability, string $field) {
    $record = componentCapabilityForTest($this->component, $capability, [$field => "node\0server.js"]);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), $field);
    expect($record->config)->toBe([$field => "node\0server.js"]);

    $command = 'echo "$PORT" && node --version | cat';
    $record->config = [$field => $command];
    $record->handler()->validate($this->component, $this->server);

    expect($record->config[$field])->toBe($command);
    $this->assertDatabaseCount('component_capabilities', 0);
})->with([['web', 'start_command'], ['scheduler', 'command']]);

test('capability validation rejects cross-project servers', function (string $capability) {
    $this->component->environment->application->update(['project_id' => \App\Models\Project::factory()->create()->id]);
    $record = componentCapabilityForTest($this->component, $capability);

    assertCapabilityValidationError(fn () => $record->handler()->validate($this->component, $this->server), 'capabilities');

    expect($record->config)->toBeNull();
    $this->assertDatabaseCount('component_capabilities', 0);
})->with(['web', 'worker', 'scheduler']);
