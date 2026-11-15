<?php

use App\Enums\UserRole;
use App\Enums\WorkflowRunStatus;
use App\Events\SocketEvent;
use App\Jobs\Workflow\RunJob;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\NotificationChannels\Email;
use App\NotificationChannels\Email\NotificationMail;
use App\NotificationChannels\Slack;
use App\WorkflowActions\General\Notify;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    Event::fake([SocketEvent::class]);
});

test('see workflows list', function () {
    $this->actingAs($this->user);

    Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->get(route('workflows'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('workflows/index'));
});

test('see workflow', function () {
    $this->actingAs($this->user);

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->get(route('workflows.show', $workflow))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('workflows/show'));
});

test('create workflow', function () {
    $this->actingAs($this->user);

    $this->post(route('workflows.store'), [
        'name' => 'My Workflow',
    ])->assertRedirect();

    $this->assertDatabaseHas('workflows', [
        'project_id' => $this->user->current_project_id,
        'name' => 'My Workflow',
    ]);
});

test('delete workflow', function () {
    $this->actingAs($this->user);

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->delete(route('workflows.destroy', $workflow))
        ->assertRedirect();

    $this->assertSoftDeleted('workflows', [
        'id' => $workflow->id,
    ]);
});

test('workflow run routes reject runs belonging to another workflow', function (string $route, bool $foreignProject) {
    Storage::fake('server-logs');
    $this->actingAs($this->user);

    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);
    $otherWorkflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $foreignProject ? Project::factory()->create()->id : $workflow->project_id,
    ]);
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $otherWorkflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::COMPLETED,
    ]);
    $run->log('Private workflow output');

    $this->get(route($route, ['workflow' => $workflow, 'workflowRun' => $run]))
        ->assertNotFound()
        ->assertDontSee('Private workflow output');
})->with(['workflow-runs.show', 'workflow-runs.log'])->with([false, true]);

test('workflow run routes allow runs belonging to the authorized workflow', function () {
    Storage::fake('server-logs');
    $this->actingAs($this->user);
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::COMPLETED,
    ]);
    $run->log('Authorized workflow output');

    $this->get(route('workflow-runs.show', ['workflow' => $workflow, 'workflowRun' => $run]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('workflow-runs/show')
            ->where('workflowRun.id', $run->id));
    $this->get(route('workflow-runs.log', ['workflow' => $workflow, 'workflowRun' => $run]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSee('Authorized workflow output');
});

test('workflow notification sends only through the selected authorized channel', function (string $scope, bool $legacyEmail) {
    Mail::fake();
    Http::fake();
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $this->user->current_project_id]);
    $channel = NotificationChannel::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Email::id(),
        'data' => ['email' => 'selected@example.com'],
        'project_id' => $scope === 'scoped' ? $workflow->project_id : null,
    ]);
    NotificationChannel::factory()->create([
        'provider' => Slack::id(),
        'data' => ['webhook_url' => 'https://example.com/foreign-channel'],
    ]);

    if ($scope !== 'session') {
        $abilities = $scope === 'scoped' ? ['write', 'project:'.$workflow->project_id] : ['write'];
        $this->user->withAccessToken($this->user->createToken('workflow', $abilities)->accessToken);
    }

    $input = [
        'notification_channel_id' => $channel->id,
        'message' => 'Private workflow notification',
    ];
    if ($legacyEmail) {
        $input['email'] = 'deleted-user@example.com';
    }

    (new Notify($this->user, $workflow))->run($input);

    Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo('selected@example.com'));
    Mail::assertSentCount(1);
    Http::assertNothingSent();
})->with(['session', 'scoped', 'unrestricted'])->with([false, true]);

test('workflow notification rejects foreign and token excluded channels', function (string $scope) {
    Mail::fake();
    Http::fake();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $this->user->current_project_id]);
    $channel = NotificationChannel::factory()->create([
        'user_id' => $scope === 'foreign-owner' ? User::factory()->create()->id : $this->user->id,
        'project_id' => $scope === 'global' ? null : $project->id,
        'provider' => Email::id(),
        'data' => ['email' => 'excluded@example.com'],
    ]);
    if ($scope !== 'foreign-owner') {
        $token = $this->user->createToken('workflow', ['write', 'project:'.$workflow->project_id]);
        $this->user->withAccessToken($token->accessToken);
    }

    expect(fn () => (new Notify($this->user, $workflow))->run([
        'notification_channel_id' => $channel->id,
        'email' => $this->user->email,
        'message' => 'Private workflow notification',
    ]))->toThrow(AuthorizationException::class);

    Mail::assertNothingSent();
    Http::assertNothingSent();
})->with(['foreign-owner', 'other-project', 'global']);

test('project role policies enforce the token project scope', function (string $ability) {
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $token = $this->user->createToken('workflow', ['read', 'write', 'project:'.$this->user->current_project_id]);
    $this->user->withAccessToken($token->accessToken);

    expect($this->user->can($ability, $this->user->currentProject))->toBeTrue()
        ->and($this->user->can($ability, $project))->toBeFalse();
})->with(['view', 'update', 'delete']);

/**
 * @param  array<string, mixed>  $inputs
 * @return array<string, mixed>
 */
function vitoPestFeatureWorkflowTestNode(string $id, bool $starting = false, array $inputs = []): array
{
    return ['id' => $id, 'data' => ['action' => ['label' => $id, 'handler' => Notify::class, 'starting' => $starting, 'inputs' => $inputs, 'outputs' => []]]];
}

/**
 * @return array<string, mixed>
 */
function vitoPestFeatureWorkflowTestEdge(string $source, string $target, string $status = 'success'): array
{
    return ['id' => $source.'-'.$target.'-'.$status, 'source' => $source, 'target' => $target, 'data' => ['status' => $status]];
}

test('workflows with a cycle are rejected on save and on run', function (array $edges) {
    $this->actingAs($this->user);
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $this->user->current_project_id]);
    $nodes = [vitoPestFeatureWorkflowTestNode('a', true), vitoPestFeatureWorkflowTestNode('b')];
    $edges = array_map(fn (array $edge): array => vitoPestFeatureWorkflowTestEdge(...$edge), $edges);

    $this->put(route('workflows.update', $workflow), ['name' => 'Loop', 'nodes' => $nodes, 'edges' => $edges])
        ->assertRedirect()
        ->assertSessionHas('error', 'The workflow contains a cycle.');
    expect($workflow->fresh()->payload)->toBeNull();

    $workflow->update(['payload' => ['nodes' => $nodes, 'edges' => $edges]]);
    expect(fn () => $workflow->fresh()->getExecutionTree())->toThrow(ValidationException::class, 'The workflow contains a cycle.');
    $this->post(route('workflow-runs.store', $workflow), ['inputs' => []]);
    $this->assertDatabaseCount('workflow_runs', 0);
})->with([
    'self loop' => [[['a', 'a']]],
    'two node loop' => [[['a', 'b'], ['b', 'a', 'failure']]],
]);

test('reconverging workflow branches are built once and shared', function () {
    $nodes = [vitoPestFeatureWorkflowTestNode('n0', true)];
    $edges = [];
    for ($i = 0; $i < 40; $i++) {
        array_push($nodes, vitoPestFeatureWorkflowTestNode("a{$i}"), vitoPestFeatureWorkflowTestNode("b{$i}"), vitoPestFeatureWorkflowTestNode('n'.($i + 1)));
        array_push($edges,
            vitoPestFeatureWorkflowTestEdge("n{$i}", "a{$i}"), vitoPestFeatureWorkflowTestEdge("n{$i}", "b{$i}", 'failure'),
            vitoPestFeatureWorkflowTestEdge("a{$i}", 'n'.($i + 1)), vitoPestFeatureWorkflowTestEdge("b{$i}", 'n'.($i + 1)),
        );
    }
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $this->user->current_project_id, 'payload' => ['nodes' => $nodes, 'edges' => $edges]]);

    $tree = $workflow->getExecutionTree();

    expect($tree->success->success)->toBe($tree->failure->success)
        ->and($tree->success->success->id)->toBe('n1');
});

test('workflow action inputs are masked for members who cannot edit the workflow', function (string $via, ?UserRole $role) {
    $this->user->update(['is_admin' => false]);
    if ($role !== null) {
        $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => $role]);
    }
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'payload' => ['nodes' => [vitoPestFeatureWorkflowTestNode('a', true, ['username' => 'app', 'password' => 'db-secret-value'])], 'edges' => []],
    ]);
    $masked = $role === UserRole::USER || $via === 'read-token';

    if ($via === 'web') {
        $response = $this->actingAs($this->user)->get(route('workflows.show', $workflow))->assertSuccessful();
        $nodes = $response->inertiaProps('workflow.nodes');
        $runInputs = $response->inertiaProps('workflow.run_inputs');
    } else {
        $token = $this->user->createToken('workflow', $via === 'read-token' ? ['read'] : ['read', 'write'])->plainTextToken;
        $response = $this->withToken($token)->getJson(route('api.projects.workflows.show', [$workflow->project_id, $workflow]))->assertSuccessful();
        $nodes = $response->json('nodes');
        $runInputs = $response->json('run_inputs');
    }

    $expected = $masked ? ['username' => '', 'password' => ''] : ['username' => 'app', 'password' => 'db-secret-value'];
    expect($nodes[0]['data']['action']['inputs'])->toBe($expected)
        ->and($runInputs)->toBe($expected);
    if ($masked) {
        $response->assertDontSee('db-secret-value');
    }
    if ($via === 'web') {
        $index = $this->get(route('workflows'))->assertSuccessful();
        $role === UserRole::USER ? $index->assertDontSee('db-secret-value') : $index->assertSee('db-secret-value');
    }
})->with(['web', 'read-token', 'write-token'])->with([UserRole::USER, null]);

test('workflow saves reject nodes and edges with non string ids', function (array $nodes, array $edges) {
    $this->actingAs($this->user);
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $this->user->current_project_id]);

    $this->put(route('workflows.update', $workflow), ['name' => 'Bad', 'nodes' => $nodes, 'edges' => $edges])
        ->assertRedirect()->assertSessionHas('error');
    expect($workflow->fresh()->payload)->toBeNull();
})->with([
    'array node id' => [[['id' => ['x'], 'data' => ['action' => ['starting' => true]]]], []],
    'array edge target' => [[vitoPestFeatureWorkflowTestNode('a', true)], [['source' => 'a', 'target' => ['a']]]],
    'duplicate node id' => [[vitoPestFeatureWorkflowTestNode('a', true), vitoPestFeatureWorkflowTestNode('a')], []],
]);

test('queued workflow runs keep action inputs out of the plain job payload', function () {
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'payload' => ['nodes' => [vitoPestFeatureWorkflowTestNode('a', true, ['password' => 'db-secret-value'])], 'edges' => []],
    ]);
    $run = WorkflowRun::factory()->create(['workflow_id' => $workflow->id, 'user_id' => $this->user->id, 'status' => WorkflowRunStatus::RUNNING]);
    $job = new RunJob($run, $this->user, $workflow, $workflow->getExecutionTree(), []);

    $payload = (fn () => $this->createPayload($job, 'ssh'))->call(app('queue')->connection('redis'));

    expect($payload)->not->toContain('db-secret-value')
        ->and(decrypt(json_decode($payload, true)['data']['command']))->toContain('db-secret-value');
});
