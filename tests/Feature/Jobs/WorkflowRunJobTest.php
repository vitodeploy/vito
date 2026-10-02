<?php

use App\Actions\Workflow\RunWorkflow;
use App\DTOs\WorkflowActionDTO;
use App\Enums\UserRole;
use App\Enums\WorkflowRunStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\Workflow\RunJob;
use App\Models\Project;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\WorkflowActions\General\HttpCall;
use App\WorkflowActions\General\RunCommand;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    Event::fake([SocketEvent::class]);
});

test('run job failed sets status to failed and logs', function () {
    Log::spy();

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    /** @var WorkflowRun $run */
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
    ]);

    $executionTree = new WorkflowActionDTO(
        label: 'Test Action',
        handler: 'App\\WorkflowActions\\SomeAction',
        outputs: [],
        inputs: [],
        id: 'node-1',
    );

    $job = new RunJob($run, $this->user, $workflow, $executionTree, ['inputs' => []]);
    $job->failed(new Exception('Workflow execution failed'));

    $run->refresh();

    expect($run->status)->toEqual(WorkflowRunStatus::FAILED);

    Log::shouldHaveReceived('error')
        ->withArgs(function (string $message, array $context) {
            return $message === 'run-workflow-failed'
                && $context['error'] === 'Workflow execution failed';
        })
        ->once();
});

test('verbose run logs command output', function () {
    Storage::fake('server-logs');
    SSH::fake('command-output-line');

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    /** @var WorkflowRun $run */
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
        'verbose' => true,
        'log_disk' => 'server-logs',
        'log_path' => 'workflow_run_test.log',
    ]);

    $executionTree = new WorkflowActionDTO(
        label: 'Run Command',
        handler: RunCommand::class,
        outputs: [],
        inputs: [
            'server_id' => $this->server->id,
            'command' => 'echo hello',
            'user' => null,
        ],
        id: 'node-1',
    );

    $job = new RunJob($run, $this->user, $workflow, $executionTree, ['inputs' => []]);
    $job->handle();

    $run->refresh();

    expect($run->status)->toEqual(WorkflowRunStatus::COMPLETED);
    $this->assertStringContainsString('command-output-line', $run->getLogContent());
});

test('verbose log does not leak after run', function () {
    Storage::fake('server-logs');
    SSH::fake('command-output-line');

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    /** @var WorkflowRun $run */
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
        'verbose' => true,
        'log_disk' => 'server-logs',
        'log_path' => 'workflow_run_test.log',
    ]);

    $executionTree = new WorkflowActionDTO(
        label: 'Run Command',
        handler: RunCommand::class,
        outputs: [],
        inputs: [
            'server_id' => $this->server->id,
            'command' => 'echo hello',
            'user' => null,
        ],
        id: 'node-1',
    );

    $job = new RunJob($run, $this->user, $workflow, $executionTree, ['inputs' => []]);
    $job->handle();

    $this->server->ssh()->exec('echo after-run');

    $this->assertStringNotContainsString('after-run', $run->refresh()->getLogContent());
});

test('workflow workers preserve session project access and restore execution context', function (bool $member, bool $legacy) {
    SSH::fake();
    Storage::fake('server-logs');
    Queue::fake();
    $project = Project::factory()->create();
    if ($member) {
        $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    }
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $project->id,
    ]);
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
    ]);
    $tree = new WorkflowActionDTO('Command', RunCommand::class, [], [
        'server_id' => $this->server->id,
        'command' => 'echo session-workflow',
        'user' => null,
    ], 'command');
    $job = new RunJob($run, $this->user, $workflow, $tree, []);
    if ($legacy) {
        (function (): void {
            unset($this->accessTokenId);
        })->call($job);
    }
    $job = unserialize(serialize($job));
    config()->set('queue.connections.ssh.driver', 'database');
    config()->set('queue.connections.default.driver', 'database');

    $job->withFakeQueueInteractions()->handle();

    if ($member && ! $legacy) {
        SSH::assertExecutedContains('echo session-workflow');
        $job->assertNotFailed();
    } else {
        SSH::assertNotExecutedContains('echo session-workflow');
        $job->assertFailedWith(AuthorizationException::class);
    }
    expect(config('queue.connections.ssh.driver'))->toBe('database')
        ->and(config('queue.connections.default.driver'))->toBe('database')
        ->and($this->user->currentAccessToken())->toBeNull()
        ->and((new ReflectionProperty($job, 'user'))->getValue($job)->currentAccessToken())->toBeNull();
})->with([
    'authorized cross-project session' => [true, false],
    'session without workflow membership' => [false, false],
    'legacy job without authentication context' => [true, true],
]);

test('workflow jobs recheck current token scope between actions and failure branches', function (string $change, bool $hasFailureBranch) {
    SSH::fake();
    Storage::fake('server-logs');
    Queue::fake();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $project->id]);
    $token = $this->user->createToken('workflow', ['write', 'project:'.$project->id, 'project:'.$this->server->project_id]);
    $this->user->withAccessToken($token->accessToken);
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
    ]);
    $command = new WorkflowActionDTO('Command', RunCommand::class, [], [
        'server_id' => '{{response_body_raw}}',
        'command' => 'echo chained-workflow',
        'user' => null,
    ], 'command');
    $failure = new WorkflowActionDTO('Failure command', RunCommand::class, [], [
        'server_id' => $this->server->id,
        'command' => 'echo failure-workflow',
        'user' => null,
    ], 'failure');
    $tree = new WorkflowActionDTO('HTTP request', HttpCall::class, [], [
        'url' => 'https://example.com/workflow',
        'method' => 'GET',
    ], 'http', success: $command, failure: $hasFailureBranch ? $failure : null);
    Http::fake(function () use ($token, $project, $change) {
        match ($change) {
            'narrowed' => $token->accessToken->forceFill(['abilities' => ['write', 'project:'.$project->id]])->save(),
            'revoked' => $token->accessToken->delete(),
            default => null,
        };

        return Http::response((string) $this->server->id);
    });
    $job = unserialize(serialize(new RunJob($run, $this->user, $workflow, $tree, [])));

    $job->withFakeQueueInteractions()->handle();

    Http::assertSentCount(1);
    if ($change === 'unchanged') {
        SSH::assertExecutedContains('echo chained-workflow');
    } else {
        SSH::assertNotExecutedContains('echo chained-workflow');
    }
    SSH::assertNotExecutedContains('echo failure-workflow');
    if ($change === 'revoked') {
        $job->assertFailedWith(AuthorizationException::class);
    } else {
        $job->assertNotFailed();
    }
    expect((new ReflectionProperty($job, 'user'))->getValue($job)->currentAccessToken())->toBeNull();
})->with(['unchanged', 'narrowed', 'revoked'])->with([false, true]);

test('nested workflows inherit token scope without changing the outer execution context', function (bool $revoked) {
    SSH::fake();
    Storage::fake('server-logs');
    Queue::fake([RunJob::class]);
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $workflow = Workflow::factory()->create(['user_id' => $this->user->id, 'project_id' => $project->id]);
    $nested = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $project->id,
        'payload' => [
            'nodes' => [[
                'id' => 'command',
                'data' => ['action' => [
                    'label' => 'Nested command',
                    'handler' => RunCommand::class,
                    'starting' => true,
                    'inputs' => [
                        'server_id' => $this->server->id,
                        'command' => 'echo nested-workflow',
                        'user' => null,
                    ],
                ]],
            ]],
            'edges' => [],
        ],
    ]);
    $token = $this->user->createToken('workflow', ['write', 'project:'.$project->id]);
    $this->user->withAccessToken($token->accessToken);
    $run = WorkflowRun::factory()->create([
        'workflow_id' => $workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
    ]);
    $tree = new WorkflowActionDTO('HTTP request', HttpCall::class, [], [
        'url' => 'https://example.com/workflow',
        'method' => 'GET',
    ], 'http');
    $job = unserialize(serialize(new RunJob($run, $this->user, $workflow, $tree, [])));
    $actor = (new ReflectionProperty($job, 'user'))->getValue($job);
    Http::fake(function () use ($actor, $nested, $token, $revoked) {
        if ($revoked) {
            $actor->currentAccessToken()->delete();
        }
        app(RunWorkflow::class)->run($actor, $nested, ['inputs' => []]);
        $nestedJob = unserialize(serialize(Queue::pushed(RunJob::class)->first()));
        $nestedJob->withFakeQueueInteractions()->handle();

        if ($revoked) {
            $nestedJob->assertFailedWith(AuthorizationException::class);
        }
        expect($actor->currentAccessToken()->id)->toBe($token->accessToken->id);

        return Http::response('ok');
    });

    $job->withFakeQueueInteractions()->handle();

    Http::assertSentCount(1);
    Queue::assertPushed(RunJob::class, 1);
    SSH::assertNotExecutedContains('echo nested-workflow');
    $job->assertNotFailed();
    expect($actor->currentAccessToken())->toBeNull();
})->with([false, true]);
