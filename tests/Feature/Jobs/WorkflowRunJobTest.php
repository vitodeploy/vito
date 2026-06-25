<?php

namespace Tests\Feature\Jobs;

use App\DTOs\WorkflowActionDTO;
use App\Enums\WorkflowRunStatus;
use App\Facades\SSH;
use App\Jobs\Workflow\RunJob;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\WorkflowActions\General\RunCommand;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowRunJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_job_failed_sets_status_to_failed_and_logs(): void
    {
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

        $this->assertEquals(WorkflowRunStatus::FAILED, $run->status);

        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context) {
                return $message === 'run-workflow-failed'
                    && $context['error'] === 'Workflow execution failed';
            })
            ->once();
    }

    public function test_verbose_run_logs_command_output(): void
    {
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

        $this->assertEquals(WorkflowRunStatus::COMPLETED, $run->status);
        $this->assertStringContainsString('command-output-line', $run->getLogContent());
    }

    public function test_verbose_log_does_not_leak_after_run(): void
    {
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
    }
}
