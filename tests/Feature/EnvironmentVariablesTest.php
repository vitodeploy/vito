<?php

use App\Actions\EnvironmentVariable\DeleteEnvironmentVariable;
use App\Actions\EnvironmentVariable\ImportEnvironmentVariables;
use App\Actions\EnvironmentVariable\UpsertEnvironmentVariable;
use App\Enums\EnvironmentStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    $this->application = Application::factory()->create(['project_id' => $this->user->current_project_id, 'runtime' => 'node']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create();
    $this->url = '/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/variables';
});

test('reserved shell keys are rejected by saves and imports before mutation', function (string $key) {
    $variable = EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'ORIGINAL', 'value' => 'unchanged']);
    $input = ['key' => $key, 'value' => 'sensitive-value', 'is_secret' => true];
    $this->postJson($this->url, $input)->assertUnprocessable()->assertJsonValidationErrors('key');
    $this->putJson($this->url.'/'.$variable->id, $input)->assertUnprocessable()->assertJsonValidationErrors('key');
    foreach (['preview', 'import'] as $operation) {
        $response = $this->postJson($this->url.'/'.$operation, ['content' => "GOOD=ok\n{$key}='sensitive-value'", 'secret_keys' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('content');
        expect($response->json('errors.content.0'))->toContain('line 2.')->not->toContain('sensitive-value');
    }
    $this->assertDatabaseCount('environment_variables', 1);
    $this->assertDatabaseHas('environment_variables', ['id' => $variable->id, 'key' => 'ORIGINAL']);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => false]);
    expect($variable->fresh()->value)->toBe('unchanged');
    Event::assertNotDispatched(SocketEvent::class);
})->with([
    'UID', 'EUID', 'PPID', 'BASH', 'BASH_ENV', 'BASHOPTS', 'BASHPID', 'BASH_ALIASES', 'BASH_XTRACEFD', 'SHELLOPTS',
    'SECONDS', 'RANDOM', 'SRANDOM', 'EPOCHSECONDS', 'EPOCHREALTIME', 'LINENO', 'IFS', 'OPTIND', 'OPTERR', 'SHLVL',
    'PWD', 'OLDPWD', 'DIRSTACK', 'GROUPS', 'FUNCNAME', 'PIPESTATUS', 'ENV', 'PROMPT_COMMAND', 'PS4', 'GLOBIGNORE',
    'POSIXLY_CORRECT', 'TMOUT', 'HISTFILE', 'HISTFILESIZE', 'COLUMNS', 'LINES', 'COMP_WORDS', 'READLINE_POINT', '_',
]);

test('ordinary runtime and application keys remain supported', function () {
    $keys = ['PATH', 'HOME', 'LANG', 'LC_ALL', 'PORT', 'NODE_OPTIONS', 'APP_BASH_ENV', 'SECONDS_TOTAL', 'API_KEY'];
    $content = implode("\n", array_map(fn (string $key): string => $key."='literal'", $keys));
    $this->post($this->url.'/preview', ['content' => $content])->assertSessionHasNoErrors();
    $this->post($this->url.'/import', ['content' => $content, 'secret_keys' => []])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('environment_variables', count($keys));
});

test('variable notifications are secret free coalesced and dispatched only after commit', function (string $operation, bool $rollback) {
    $variable = EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'ORIGINAL']);
    try {
        DB::transaction(function () use ($variable, $operation, $rollback): void {
            if ($operation === 'save') {
                app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'ORIGINAL', 'value' => 'sensitive-value', 'is_secret' => true], $variable);
            } elseif ($operation === 'import') {
                app(ImportEnvironmentVariables::class)->import($this->environment, ['content' => "FIRST=sensitive-value\nSECOND=sensitive-value", 'secret_keys' => ['FIRST', 'SECOND']]);
            } else {
                app(DeleteEnvironmentVariable::class)->delete($this->environment, $variable);
            }
            Event::assertNotDispatched(SocketEvent::class);
            if ($rollback) {
                throw new RuntimeException('Rollback');
            }
        });
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Rollback');
    }
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => ! $rollback]);
    if ($rollback) {
        Event::assertNotDispatched(SocketEvent::class);
        $this->assertDatabaseCount('environment_variables', 1);
    } else {
        Event::assertDispatchedTimes(SocketEvent::class, 1);
        Event::assertDispatched(SocketEvent::class, function (SocketEvent $event): bool {
            expect($event->data->data)->toBe(['id' => $this->environment->id, 'application_id' => $this->application->id]);

            return $event->data->type === 'environment.updated' && $event->data->projectId === $this->application->project_id;
        });
    }
})->with(['save', 'import', 'delete'])->with([false, true]);

test('environment variables are encrypted preserve whitespace and mark only their environment for redeploy', function (string $value, string $expected) {
    $sibling = Component::factory()->for($this->environment)->create();
    $other = Component::factory()->create();
    $this->post($this->url, ['key' => 'MESSAGE', 'value' => $value, 'is_secret' => true, 'environment_id' => $other->environment_id])->assertSessionHasNoErrors();

    $variable = $this->environment->variables()->sole();
    expect($variable->value)->toBe($expected)->and($variable->getRawOriginal('value'))->not->toBe($expected)
        ->and($variable->toArray())->not->toHaveKey('value');
    $this->assertDatabaseHas('environment_variables', ['id' => $variable->id, 'environment_id' => $this->environment->id, 'key' => 'MESSAGE', 'is_secret' => true]);
    foreach ([$this->component, $sibling] as $component) {
        $this->assertDatabaseHas('components', ['id' => $component->id, 'needs_redeploy' => true]);
    }
    $this->assertDatabaseHas('components', ['id' => $other->id, 'needs_redeploy' => false]);
    expect(SSH::getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
    Http::assertNothingSent();
})->with([
    ['  edge spaces  ', '  edge spaces  '], ['', ''], ["first\r\nsecond\rthird\n", "first\nsecond\nthird\n"],
]);

test('environment variable inputs are validated', function (string $field, mixed $value) {
    $this->postJson($this->url, array_replace(['key' => 'VALID_KEY', 'value' => 'value', 'is_secret' => false], [$field => $value]))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('environment_variables', 0);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => false]);
})->with([
    ['key', 'lowercase'], ['key', '1START'], ['key', "TRAILING\n"], ['key', 'WITH SPACE'], ['key', ''], ['key', str_repeat('A', 256)],
    ['value', "before\0after"], ['value', []], ['value', null], ['is_secret', 'invalid'],
]);

test('duplicate keys are rejected within an environment including renames', function () {
    $first = EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'FIRST']);
    $second = EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'SECOND']);
    $input = ['key' => 'FIRST', 'value' => 'changed', 'is_secret' => false];
    $this->postJson($this->url, $input)->assertUnprocessable()->assertJsonValidationErrors('key');
    $this->putJson($this->url.'/'.$second->id, $input)->assertUnprocessable()->assertJsonValidationErrors('key');
    $this->put($this->url.'/'.$first->id, $input)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('environment_variables', ['id' => $first->id, 'key' => 'FIRST']);
    expect($first->fresh()->value)->toBe('changed');
});

test('masked secrets keep their value unless explicitly replaced and cannot be revealed by toggling', function () {
    $variable = EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'SECRET', 'value' => 'never-return-this', 'is_secret' => true]);
    $this->put($this->url.'/'.$variable->id, ['key' => 'RENAMED_SECRET', 'is_secret' => true])->assertSessionHasNoErrors();
    expect($variable->fresh()->value)->toBe('never-return-this');
    $this->putJson($this->url.'/'.$variable->id, ['key' => 'RENAMED_SECRET', 'is_secret' => false])->assertUnprocessable()->assertJsonValidationErrors('value');
    $this->put($this->url.'/'.$variable->id, ['key' => 'RENAMED_SECRET', 'value' => '', 'is_secret' => true])->assertSessionHasNoErrors();
    expect($variable->fresh()->value)->toBe('');
    $this->put($this->url.'/'.$variable->id, ['key' => 'RENAMED_SECRET', 'value' => 'public replacement', 'is_secret' => false])->assertSessionHasNoErrors();
    expect($variable->fresh()->value)->toBe('public replacement');
});

test('variables page masks secrets and exposes readonly computed values and redeploy state', function () {
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'API_SECRET', 'value' => 'never-return-this', 'is_secret' => true]);
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'MESSAGE', 'value' => 'public', 'is_secret' => false]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web', 'config' => ['port' => 12345]]);
    $this->component->needs_redeploy = true;
    $this->component->save();

    $response = $this->get($this->url)->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/variables')->has('variables', 3)
        ->where('variables.0.key', 'API_SECRET')->where('variables.0.value', null)->where('variables.0.has_value', true)
        ->where('variables.1.value', 'public')->where('variables.2.source', 'vito')->where('variables.2.value', '12345')
        ->where('variables.2.id', null)->where('variables.2.component_name', $this->component->name)
        ->where('environment.needs_redeploy', true)->where('warnings', [])->where('canUpdate', true));
    expect($response->getContent())->not->toContain('never-return-this');
});

test('variables resolve component servers in one query', function () {
    Component::factory()->count(2)->for($this->environment)->create();
    DB::enableQueryLog();
    DB::flushQueryLog();
    try {
        app(\App\Actions\EnvironmentVariable\GetEnvironmentVariables::class)->get($this->environment->fresh());
        $queries = collect(DB::getQueryLog())->filter(fn (array $query): bool => preg_match('/from ["`]servers["`]/i', $query['query']) === 1);
        expect($queries)->toHaveCount(1);
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
});

test('import preview contains only keys and secret guesses and import honors toggles and multiline values', function () {
    $content = "# comment\r\nNAME=example\r\nPASSWORD=private\r\nAPI_SECRET=private\r\nTOKEN=private\r\nAPI_KEY=private\r\nKEYBOARD=public\r\nPRIVATE_PEM=\"-----BEGIN KEY-----\r\nline two\r\n-----END KEY-----\"\r\nREFERENCE='\${NAME}'\r\nDATABASE_URL=postgres://user:private@localhost/data\r\nREDIS_URL=redis://user:private@localhost";
    $this->post($this->url.'/preview', ['content' => $content])->assertSessionHasNoErrors()->assertSessionHas('data.variables', [
        ['key' => 'NAME', 'is_secret' => false], ['key' => 'PASSWORD', 'is_secret' => true], ['key' => 'API_SECRET', 'is_secret' => true],
        ['key' => 'TOKEN', 'is_secret' => true], ['key' => 'API_KEY', 'is_secret' => true], ['key' => 'KEYBOARD', 'is_secret' => true],
        ['key' => 'PRIVATE_PEM', 'is_secret' => true], ['key' => 'REFERENCE', 'is_secret' => false],
        ['key' => 'DATABASE_URL', 'is_secret' => true], ['key' => 'REDIS_URL', 'is_secret' => true],
    ]);
    $this->assertDatabaseCount('environment_variables', 0);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => false]);
    EnvironmentVariable::factory()->for($this->environment)->create(['key' => 'NAME', 'value' => 'old']);
    $this->post($this->url.'/import', ['content' => $content, 'secret_keys' => ['NAME', 'PRIVATE_PEM', 'DATABASE_URL', 'REDIS_URL']])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('environment_variables', 10);
    $this->assertDatabaseHas('environment_variables', ['environment_id' => $this->environment->id, 'key' => 'NAME', 'is_secret' => true]);
    $this->assertDatabaseHas('environment_variables', ['environment_id' => $this->environment->id, 'key' => 'PASSWORD', 'is_secret' => false]);
    expect($this->environment->variables()->where('key', 'PRIVATE_PEM')->sole()->value)->toBe("-----BEGIN KEY-----\nline two\n-----END KEY-----")
        ->and($this->environment->variables()->where('key', 'REFERENCE')->sole()->value)->toBe('${NAME}');
    $response = $this->get($this->url)->assertSuccessful();
    expect($response->getContent())->not->toContain('-----BEGIN KEY-----', 'postgres://user:private', 'redis://user:private');
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
    expect(SSH::getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
});

test('invalid imports fail atomically with safe line numbers', function (string $content, int $line) {
    foreach (['preview', 'import'] as $operation) {
        $response = $this->postJson($this->url.'/'.$operation, ['content' => $content, 'secret_keys' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('content');
        expect($response->json('errors.content.0'))->toContain('line '.$line.'.')->not->toContain('sensitive-value');
    }
    $this->assertDatabaseCount('environment_variables', 0);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => false]);
})->with([
    ["GOOD=ok\nBAD=sensitive-value with spaces", 2], ["GOOD=ok\nBAD=\"sensitive-value", 2],
    ["GOOD=ok\nBAD='sensitive-value", 2], ["GOOD=ok\nlower=sensitive-value", 2],
    ["GOOD=ok\nGOOD=sensitive-value", 2], ["GOOD=ok\nBAD=sensitive-value\0", 2],
    ["PEM=\"first\nsecond\"\nBAD=sensitive-value with spaces", 3],
]);

test('import validates secret selection without reflecting invalid selections', function () {
    $this->postJson($this->url.'/import', ['content' => 'NAME=example', 'secret_keys' => ['UNKNOWN']])
        ->assertUnprocessable()->assertJsonValidationErrors('secret_keys.0');
    $this->assertDatabaseCount('environment_variables', 0);
});

test('validation failures do not flash secrets or import contents', function () {
    $this->post($this->url, ['key' => 'bad', 'value' => 'sensitive-value', 'is_secret' => true])->assertSessionHasErrors('key');
    expect(session()->getOldInput())->not->toHaveKey('value');
    $this->post($this->url.'/import', ['content' => 'BAD=sensitive-value with spaces', 'secret_keys' => []])->assertSessionHasErrors('content');
    expect(session()->getOldInput())->not->toHaveKey('content');
});

test('deleting a variable marks all components and environment deletion cascades variables', function () {
    $variable = EnvironmentVariable::factory()->for($this->environment)->create();
    $this->delete($this->url.'/'.$variable->id)->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('environment_variables', ['id' => $variable->id]);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
    $remaining = EnvironmentVariable::factory()->for($this->environment)->create();
    $this->environment->delete();
    $this->assertDatabaseMissing('environment_variables', ['id' => $remaining->id]);
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('variable routes enforce both parent bindings', function () {
    $variable = EnvironmentVariable::factory()->create();
    $input = ['key' => 'NAME', 'value' => 'value', 'is_secret' => false];
    $this->putJson($this->url.'/'.$variable->id, $input)->assertNotFound();
    $this->deleteJson($this->url.'/'.$variable->id)->assertNotFound();
    $other = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $this->postJson('/applications/'.$other->id.'/environments/'.$this->environment->id.'/variables', $input)->assertNotFound();
});

test('variable endpoints reject guests other projects and readonly mutations', function (string $access) {
    $variable = EnvironmentVariable::factory()->for($this->environment)->create();
    if ($access === 'guest') {
        auth()->logout();
    } elseif ($access === 'other-project') {
        $this->application->update(['project_id' => Project::factory()->create()->id]);
    } else {
        $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
        $this->get($this->url)->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page->where('canUpdate', false));
    }
    $status = $access === 'guest' ? 401 : 403;
    if ($access !== 'readonly') {
        $this->getJson($this->url)->assertStatus($status);
    }
    $this->postJson($this->url, [])->assertStatus($status);
    $this->putJson($this->url.'/'.$variable->id, [])->assertStatus($status);
    $this->deleteJson($this->url.'/'.$variable->id)->assertStatus($status);
    $this->postJson($this->url.'/preview', [])->assertStatus($status);
    $this->postJson($this->url.'/import', [])->assertStatus($status);
})->with(['guest', 'other-project', 'readonly']);

test('variable mutations are blocked during teardown', function () {
    $variable = EnvironmentVariable::factory()->for($this->environment)->create();
    $this->environment->status = EnvironmentStatus::DELETING;
    $this->environment->save();
    $input = ['key' => 'NAME', 'value' => 'value', 'is_secret' => false];
    $this->postJson($this->url, $input)->assertUnprocessable();
    $this->putJson($this->url.'/'.$variable->id, $input)->assertUnprocessable();
    $this->deleteJson($this->url.'/'.$variable->id)->assertUnprocessable();
    $this->postJson($this->url.'/import', ['content' => 'NAME=value', 'secret_keys' => []])->assertUnprocessable();
    $this->assertDatabaseCount('environment_variables', 1);
});
