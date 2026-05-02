<?php

namespace App\Http\Controllers\API;

use App\Actions\Workflow\RunWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowRunResource;
use App\Models\Project;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/workflows/{workflow}/runs')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class WorkflowRunController extends Controller
{
    #[Get('/', name: 'api.projects.workflows.runs', middleware: 'ability:read')]
    public function index(Project $project, Workflow $workflow): ResourceCollection
    {
        $this->authorize('view', $workflow);

        $this->validateRoute($project, $workflow);

        $workflowRuns = $workflow->runs()
            ->orderByDesc('created_at')
            ->simplePaginate(25);

        return WorkflowRunResource::collection($workflowRuns);
    }

    #[Post('/', name: 'api.projects.workflows.runs.store', middleware: 'ability:write')]
    public function store(Request $request, Project $project, Workflow $workflow): WorkflowRunResource
    {
        $this->authorize('update', $workflow);

        $this->validateRoute($project, $workflow);

        $run = app(RunWorkflow::class)->run(user(), $workflow, $request->all());

        return new WorkflowRunResource($run);
    }

    #[Get('{workflowRun}', name: 'api.projects.workflows.runs.show', middleware: 'ability:read')]
    public function show(Project $project, Workflow $workflow, WorkflowRun $workflowRun): WorkflowRunResource
    {
        $this->authorize('view', $workflow);

        $this->validateRoute($project, $workflow);

        if ($workflowRun->workflow_id !== $workflow->id) {
            abort(404, 'Workflow run not found for this workflow');
        }

        return new WorkflowRunResource($workflowRun);
    }

    #[Get('{workflowRun}/log', name: 'api.projects.workflows.runs.log', middleware: 'ability:read')]
    public function log(Project $project, Workflow $workflow, WorkflowRun $workflowRun): Response
    {
        $this->authorize('view', $workflow);

        $this->validateRoute($project, $workflow);

        if ($workflowRun->workflow_id !== $workflow->id) {
            abort(404, 'Workflow run not found for this workflow');
        }

        $logContent = $workflowRun->getLogContent();

        return response($logContent, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function validateRoute(Project $project, Workflow $workflow): void
    {
        if ($project->id !== $workflow->project_id) {
            abort(404, 'Workflow not found in project');
        }
    }
}
