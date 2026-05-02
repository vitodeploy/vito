<?php

namespace App\Http\Controllers\API;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Actions\SourceControl\DeleteSourceControl;
use App\Actions\SourceControl\EditSourceControl;
use App\Http\Controllers\Controller;
use App\Http\Resources\SourceControlResource;
use App\Models\Project;
use App\Models\SourceControl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * @deprecated Use UserSourceControlController instead. This controller will be removed in a future version.
 */
#[Prefix('api/projects/{project}/source-controls')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class SourceControlController extends Controller
{
    /**
     * @deprecated Use GET /api/source-controls instead
     */
    #[Get('/', name: 'api.projects.source-controls', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', SourceControl::class);

        $sourceControls = SourceControl::getByProjectId($project->id, user())->simplePaginate(25);

        return SourceControlResource::collection($sourceControls);
    }

    /**
     * @deprecated Use POST /api/source-controls instead
     */
    #[Post('/', name: 'api.projects.source-controls.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project): SourceControlResource
    {
        $this->authorize('create', SourceControl::class);

        $sourceControl = app(ConnectSourceControl::class)->connect(user(), $request->all());

        return new SourceControlResource($sourceControl);
    }

    /**
     * @deprecated Use GET /api/source-controls/{sourceControl} instead
     */
    #[Get('{sourceControl}', name: 'api.projects.source-controls.show', middleware: 'ability:read')]
    public function show(Project $project, SourceControl $sourceControl): SourceControlResource
    {
        $this->authorize('view', $sourceControl);

        $this->validateRoute($project, $sourceControl);

        return new SourceControlResource($sourceControl);
    }

    /**
     * @deprecated Use PUT /api/source-controls/{sourceControl} instead
     */
    #[Put('{sourceControl}', name: 'api.projects.source-controls.update', middleware: 'ability:write')]
    public function update(Request $request, Project $project, SourceControl $sourceControl): SourceControlResource
    {
        $this->authorize('update', $sourceControl);

        $this->validateRoute($project, $sourceControl);

        $sourceControl = app(EditSourceControl::class)->edit($sourceControl, $request->all());

        return new SourceControlResource($sourceControl);
    }

    /**
     * @deprecated Use DELETE /api/source-controls/{sourceControl} instead
     */
    #[Delete('{sourceControl}', name: 'api.projects.source-controls.delete', middleware: 'ability:write')]
    public function delete(Project $project, SourceControl $sourceControl): Response
    {
        $this->authorize('delete', $sourceControl);

        $this->validateRoute($project, $sourceControl);

        app(DeleteSourceControl::class)->delete($sourceControl);

        return response()->noContent();
    }

    private function validateRoute(Project $project, SourceControl $sourceControl): void
    {
        if (! $sourceControl->project_id) {
            return;
        }

        if ($project->id !== $sourceControl->project_id) {
            abort(404, 'Source Control not found in project');
        }
    }
}
