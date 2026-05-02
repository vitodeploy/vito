<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowResource;
use App\Models\Project;
use App\Models\Workflow;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/workflows')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class WorkflowController extends Controller
{
    #[Get('/', name: 'api.projects.workflows', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [Workflow::class, $project]);

        $workflows = $project->workflows()
            ->orderBy('created_at', 'desc')
            ->simplePaginate(25);

        return WorkflowResource::collection($workflows);
    }

    #[Get('{workflow}', name: 'api.projects.workflows.show', middleware: 'ability:read')]
    public function show(Project $project, Workflow $workflow): WorkflowResource
    {
        $this->authorize('view', $workflow);

        $this->validateRoute($project, $workflow);

        return new WorkflowResource($workflow);
    }

    #[Delete('{workflow}', name: 'api.projects.workflows.delete', middleware: 'ability:write')]
    public function delete(Project $project, Workflow $workflow): Response
    {
        $this->authorize('delete', $workflow);

        $this->validateRoute($project, $workflow);

        $workflow->delete();

        return response()->noContent();
    }

    private function validateRoute(Project $project, Workflow $workflow): void
    {
        if ($project->id !== $workflow->project_id) {
            abort(404, 'Workflow not found in project');
        }
    }
}
