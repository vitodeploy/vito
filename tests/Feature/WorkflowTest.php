<?php

use App\Enums\UserRole;
use App\Enums\WorkflowRunStatus;
use App\Events\SocketEvent;
use App\Models\NotificationChannel;
use App\Models\Project;
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
        'user_id' => $scope === 'foreign-owner' ? $this->notificationChannel->user_id : $this->user->id,
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
