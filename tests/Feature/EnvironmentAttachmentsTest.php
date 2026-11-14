<?php

use App\Actions\Database\DeleteDatabase;
use App\Actions\EnvironmentAttachment\AttachDatabase;
use App\Actions\EnvironmentAttachment\DetachEnvironmentResource;
use App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess;
use App\Enums\ComponentStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Environment;
use App\Models\Project;
use App\Models\Server;
use App\Support\Testing\SSHFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Http::preventStrayRequests();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->actingAs($this->user);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->database = Database::factory()->for($this->server)->create(['name' => 'app_data']);
    $this->input = ['server_id' => $this->server->id, 'database_id' => $this->database->id];
    $this->url = '/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/resources';
});

test('attaching an existing database creates encrypted managed credentials without borrowing existing users', function () {
    $user = DatabaseUser::factory()->for($this->server)->create(['databases' => [$this->database->name]]);
    $this->post($this->url.'/database', [...$this->input, 'username' => $user->username, 'password' => 'untrusted'])->assertSessionHasNoErrors();
    $attachment = $this->environment->attachments()->sole();
    expect($attachment->credentials['password'])->not->toBe('untrusted')
        ->and($attachment->getRawOriginal('credentials'))->not->toContain($attachment->credentials['password'])
        ->and($attachment->toArray())->not->toHaveKeys(['credentials', 'access']);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
    Queue::assertPushed(SyncEnvironmentResourceAccessJob::class);
    expect(app(SyncEnvironmentResourceAccess::class)->sync($attachment))->toBeTrue();
    $managed = $attachment->databaseUsers()->sole();
    $this->assertDatabaseHas('database_users', ['id' => $managed->id, 'host' => '127.0.0.1', 'environment_attachment_id' => $attachment->id]);
    expect($managed->databases)->toBe([$this->database->name])->and($managed->username)->not->toBe($user->username);
    $this->assertDatabaseHas('database_users', ['id' => $user->id, 'environment_attachment_id' => null]);
});

test('new databases reuse database creation without creating requested external credentials', function () {
    $this->post($this->url.'/database', ['server_id' => $this->server->id, 'name' => 'application_data', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'username' => 'ignored'])
        ->assertSessionHasNoErrors();
    $attachment = $this->environment->attachments()->sole();
    $this->assertDatabaseHas('databases', ['id' => $attachment->attachable_id, 'name' => 'application_data']);
    $this->assertDatabaseHas('databases', ['id' => $attachment->attachable_id, 'status' => 'creating']);
    SSH::assertNotExecutedContains('CREATE DATABASE');
    $this->assertDatabaseCount('database_users', 0);
    app(SyncEnvironmentResourceAccess::class)->sync($attachment);
    SSH::assertExecutedContains('CREATE DATABASE');
    $this->assertDatabaseHas('databases', ['id' => $attachment->attachable_id, 'status' => 'ready']);
});

test('unsupported imported database identifiers are rejected before granting access', function () {
    $this->server->database()->update(['name' => 'postgresql', 'version' => '16']);
    $this->database->update(['name' => 'imported.data']);
    $this->postJson($this->url.'/database', $this->input)->assertUnprocessable()->assertJsonValidationErrors('database_id');
    $this->assertDatabaseCount('environment_attachments', 0);
    SSH::assertNotExecutedContains('GRANT');
});

test('failed new database provisioning retains durable intent and can retry or detach', function (bool $detach) {
    $this->server->database()->update(['name' => 'postgresql', 'version' => '16']);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'name' => 'new_data', 'charset' => 'UTF8', 'collation' => 'C']);
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'create-database')->andThrow(new SSHCommandError('lost response'));
    SSH::swap($ssh);
    expect(fn () => app(SyncEnvironmentResourceAccess::class)->sync($attachment))->toThrow(SSHCommandError::class);
    $this->assertDatabaseHas('databases', ['id' => $attachment->attachable_id, 'status' => 'creating']);
    $this->assertDatabaseHas('environment_attachments', ['id' => $attachment->id]);
    SSH::fake('');
    if ($detach) {
        app(DetachEnvironmentResource::class)->detach($attachment);
        app(SyncEnvironmentResourceAccess::class)->sync($attachment);
        $this->assertDatabaseMissing('environment_attachments', ['id' => $attachment->id]);
        $this->assertDatabaseHas('databases', ['id' => $attachment->attachable_id, 'status' => 'failed']);
        SSH::assertNotExecutedContains('DROP DATABASE');
        app(DeleteDatabase::class)->delete($this->server, Database::query()->findOrFail($attachment->attachable_id));
        SSH::assertExecutedContains('DROP DATABASE IF EXISTS');
        $this->assertSoftDeleted('databases', ['id' => $attachment->attachable_id]);
    } else {
        app(SyncEnvironmentResourceAccess::class)->sync($attachment);
        SSH::assertExecutedContains("WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'new_data')");
        $this->assertDatabaseHas('databases', ['id' => $attachment->attachable_id, 'status' => 'ready']);
    }
})->with([false, true]);

test('attachment aliases reject invalid names and duplicate defaults or resources', function () {
    foreach (['lowercase', '1START', "BA\nD", 'A-B', str_repeat('A', 65)] as $alias) {
        $this->postJson($this->url.'/database', [...$this->input, 'alias' => $alias])->assertUnprocessable()->assertJsonValidationErrors('alias');
    }
    $this->post($this->url.'/database', $this->input)->assertSessionHasNoErrors();
    $other = Database::factory()->for($this->server)->create(['name' => 'analytics']);
    $this->postJson($this->url.'/database', [...$this->input, 'database_id' => $other->id])->assertUnprocessable()->assertJsonValidationErrors('alias');
    $this->postJson($this->url.'/database', [...$this->input, 'alias' => 'COPY'])->assertUnprocessable()->assertJsonValidationErrors('resource');
    $this->post($this->url.'/database', [...$this->input, 'database_id' => $other->id, 'alias' => 'ANALYTICS'])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('environment_attachments', 2);
});

test('resource endpoints enforce project boundaries read roles and scoped binding', function () {
    $foreign = Server::factory()->create(['project_id' => Project::factory()->create()->id]);
    $this->postJson($this->url.'/database', [...$this->input, 'server_id' => $foreign->id])->assertUnprocessable()->assertJsonValidationErrors('server_id');
    $attachment = app(AttachDatabase::class)->attach($this->environment, $this->input);
    $other = Environment::factory()->for($this->application)->create();
    $base = '/applications/'.$this->application->id.'/environments/'.$other->id.'/resources';
    $this->deleteJson($base.'/'.$attachment->id)->assertNotFound();
    $this->postJson($base.'/'.$attachment->id.'/retry')->assertNotFound();
    $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->user->refresh();
    $this->get(route('environments.show', [$this->application, $this->environment, 'resource' => $attachment->id]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('resource.id', $attachment->id)->where('can.update', false));
    $this->postJson($this->url.'/database', $this->input)->assertForbidden();
    $this->deleteJson($this->url.'/'.$attachment->id)->assertForbidden();
    $this->postJson($this->url.'/'.$attachment->id.'/retry')->assertForbidden();
});

test('resource pages expose status and safe catalogues without credentials or access bookkeeping', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, $this->input);
    $this->get(route('environments.show', [$this->application, $this->environment, 'resource' => $attachment->id]))->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use ($attachment) {
            $page->component('applications/environments/show')
                ->where('resource.id', $attachment->id)->where('resource.status', 'pending')->where('resource.name', $this->database->name)
                ->where('resource.server_name', $this->server->name)->missing('resource.credentials')->missing('resource.access')
                ->where('topology.nodes.1.record_id', $attachment->id)->where('topology.nodes.1.status', 'pending')
                ->missing('resourceOptions')->where('can.update', true)
                ->reloadOnly('resourceOptions', fn (AssertableInertia $reload) => $reload->has('resourceOptions.services', 2));
            expect(json_encode($page->toArray()['props']))->not->toContain($attachment->credentials['password']);
        });
    $this->postJson($this->url.'/'.$attachment->id.'/retry')->assertUnprocessable();
    app(SyncEnvironmentResourceAccess::class)->failed($attachment->id);
    $this->post($this->url.'/'.$attachment->id.'/retry')->assertSessionHasNoErrors();
    $this->assertDatabaseHas('environment_attachments', ['id' => $attachment->id, 'status' => 'pending', 'last_error' => null]);
});

test('resource panel only resolves attachments of the routed environment', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, [...$this->input, 'alias' => 'HIDDENALIAS']);
    $other = Environment::factory()->for($this->application)->create();

    $this->get(route('environments.show', [$this->application, $other, 'resource' => $attachment->id]))->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use ($attachment) {
            $page->where('resource', null)->where('topology.nodes', []);
            expect(json_encode($page->toArray()['props']))->not->toContain($attachment->credentials['password'])->not->toContain('HIDDENALIAS');
        });
    $this->get(route('environments.show', [$this->application, $this->environment]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('resource', null));
    $this->getJson(route('environments.show', [Application::factory()->create(), $this->environment, 'resource' => $attachment->id]))->assertNotFound();
});
