<?php

use App\Actions\Component\WriteComponentEnv;
use App\Actions\EnvironmentAttachment\AttachDatabase;
use App\Actions\EnvironmentAttachment\AttachRedis;
use App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Helpers\AttachmentVariables;
use App\Helpers\ComponentVariables;
use App\Models\Application;
use App\Models\Component;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\EnvironmentVariable;
use App\Support\Testing\SSHFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Http::preventStrayRequests();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->environment->primary_component_id = $this->component->id;
    $this->environment->save();
    $this->database = Database::factory()->for($this->server)->create(['name' => 'app_data']);
});

test('resource variables encode credentials prefix aliases and expose Laravel aliases only in its preset', function (string $runtime, ?string $alias) {
    $this->application->update(['runtime' => $runtime, 'name' => 'My App']);
    $this->environment->slug = 'production';
    $this->environment->save();
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id, 'alias' => $alias]);
    $attachment->credentials = ['username' => 'managed', 'password' => 'p@ss:/?# %'];
    $attachment->save();
    $prefix = $alias ? $alias.'_' : '';
    $values = ComponentVariables::resolve($this->component->fresh());
    expect($values[$prefix.'DATABASE_URL']['value'])->toBe('mysql://managed_'.base_convert((string) $this->component->id, 10, 36).':p%40ss%3A%2F%3F%23%20%25@127.0.0.1:3306/app_data')
        ->and($values[$prefix.'DATABASE_URL']['is_secret'])->toBeTrue()
        ->and($values[$prefix.'DATABASE_PASSWORD']['is_secret'])->toBeTrue()
        ->and($values[$prefix.'DATABASE_PORT']['value'])->toBe('3306');
    expect(array_key_exists($prefix.'DB_CONNECTION', $values))->toBe($runtime === 'laravel');
    if ($runtime === 'laravel') {
        expect($values[$prefix.'DB_DATABASE']['value'])->toBe('app_data')->and($values[$prefix.'DB_PASSWORD']['is_secret'])->toBeTrue();
    }
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => $prefix.'DATABASE_HOST', 'value' => 'override', 'is_secret' => false]);
    expect(ComponentVariables::resolve($this->component->fresh())[$prefix.'DATABASE_HOST'])->toBe(['value' => 'override', 'source' => 'environment', 'is_secret' => false]);
    $this->component->update(['type' => ComponentType::LOAD_BALANCER]);
    expect(ComponentVariables::resolve($this->component->fresh()))->toHaveCount(1);
})->with(['custom', 'laravel'])->with([null, 'ANALYTICS']);

test('redis URLs and preset prefixes are provided without disclosing passwords in the variables page', function () {
    $this->application->update(['runtime' => 'laravel', 'name' => 'My App']);
    $this->environment->slug = 'production';
    $this->environment->save();
    $service = $this->server->memoryDatabase();
    $service->secret = 'p@ss:/?# %';
    $service->update(['type_data' => ['networking' => true]]);
    $attachment = app(AttachRedis::class)->attach($this->environment, ['service_id' => $service->id]);
    $values = ComponentVariables::resolve($this->component->fresh());
    expect($values['REDIS_URL']['value'])->toBe('redis://:p%40ss%3A%2F%3F%23%20%25@127.0.0.1:6379')
        ->and($values['REDIS_PREFIX']['value'])->toBe('my_app_production_')
        ->and($values['REDIS_PREFIX']['is_secret'])->toBeTrue();
    $this->actingAs($this->user)->get('/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/variables')
        ->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page->has('variables', 5)
            ->where('variables', fn ($variables) => collect($variables)->filter(fn ($row) => $row['is_secret'])->every(fn ($row) => $row['value'] === null && $row['source'] === 'resource')));
    $attachment->status = \App\Enums\EnvironmentAttachmentStatus::DELETING;
    $attachment->save();
    expect(AttachmentVariables::for($attachment, $this->component))->toBe([]);
});

test('resource connection variables reach private env uploads rather than SSH commands', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $uploads = [];
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('upload')->andReturnUsing(function ($local, $remote, $owner, $log, $siteId, $mode) use (&$uploads): void {
        expect($mode)->toBe('600');
        $uploads[] = file_get_contents($local);
    });
    SSH::swap($ssh);
    app(WriteComponentEnv::class)->write($this->component, $this->component->envPath());
    expect($uploads[1])->toContain("DATABASE_HOST='127.0.0.1'", "DATABASE_PORT='3306'", 'DATABASE_URL=', $attachment->credentials['password']);
    SSH::assertNotExecutedContains($attachment->credentials['password']);
});

test('deployments wait for resource readiness and new components cannot join a different resource set', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    expect(fn () => app(DeployEnvironment::class)->run($this->environment))->toThrow(ValidationException::class);
    app(SyncEnvironmentResourceAccess::class)->sync($attachment);
    $live = EnvironmentDeployment::factory()->for($this->environment)->create(['attachment_ids' => [], 'status' => \App\Enums\EnvironmentDeploymentStatus::FINISHED]);
    $this->environment->live_run_id = $live->id;
    $this->environment->save();
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
    $live->attachment_ids = [$attachment->id];
    $live->save();
    $join = app(DeployEnvironment::class)->join($this->environment);
    expect($join)->not->toBeNull()->and($join->attachment_ids)->toBe([$attachment->id]);
});

test('full deployments snapshot attachment membership and env generation honors that snapshot', function () {
    $source = \App\Models\SourceControl::factory()->github()->create(['project_id' => $this->server->project_id, 'user_id' => $this->user->id]);
    $this->application->update(['source_control_id' => $source->id]);
    Http::fake(['https://api.github.com/repos/*/commits/*' => Http::response(['sha' => str_repeat('a', 40), 'commit' => ['message' => 'Pinned']])]);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    app(SyncEnvironmentResourceAccess::class)->sync($attachment);
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(\App\Actions\EnvironmentDeployment\StartEnvironmentDeployment::class)->start($run->id);
    expect($run->fresh()->attachment_ids)->toBe([$attachment->id]);
    $other = \App\Models\EnvironmentAttachment::factory()->for($this->environment)->create(['alias' => 'LATER']);
    $uploads = [];
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('upload')->andReturnUsing(function ($local, $remote) use (&$uploads): void {
        $uploads[] = file_get_contents($local);
    });
    SSH::swap($ssh);
    app(\App\Actions\EnvironmentDeployment\RunComponentStage::class)->run($run->deployments()->sole()->id, \App\Enums\DeploymentStage::CLONE);
    expect(implode("\n", $uploads))->toContain('DATABASE_URL=')->not->toContain('LATER_DATABASE_URL=');
    expect($other->fresh())->not->toBeNull();
});
