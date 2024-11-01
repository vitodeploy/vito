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
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/source-controls')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'source-controls')]
class SourceControlController extends Controller
{
    #[Get('/', name: 'api.projects.source-controls', middleware: 'ability:read')]
    #[Endpoint(title: 'list')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', SourceControl::class);

        $sourceControls = SourceControl::getByProjectId($project->id)->simplePaginate(25);

        return SourceControlResource::collection($sourceControls);
    }

    #[Post('/', name: 'api.projects.source-controls.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create')]
    #[BodyParam(name: 'provider', description: 'The provider', required: true, enum: [\App\Enums\SourceControl::GITLAB, \App\Enums\SourceControl::GITHUB, \App\Enums\SourceControl::BITBUCKET])]
    #[BodyParam(name: 'name', description: 'The name of the storage provider.', required: true)]
    #[BodyParam(name: 'token', description: 'The token if provider requires api token')]
    #[BodyParam(name: 'url', description: 'The URL if the provider is Gitlab and it is self-hosted')]
    #[BodyParam(name: 'username', description: 'The username if the provider is Bitbucket')]
    #[BodyParam(name: 'password', description: 'The password if the provider is Bitbucket')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class)]
    public function create(Request $request, Project $project): SourceControlResource
    {
        $this->authorize('create', SourceControl::class);

        $this->validate($request, ConnectSourceControl::rules($request->all()));

        $sourceControl = app(ConnectSourceControl::class)->connect(auth()->user(), $project, $request->all());

        return new SourceControlResource($sourceControl);
    }

    #[Get('{sourceControl}', name: 'api.projects.source-controls.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class)]
    public function show(Project $project, SourceControl $sourceControl)
    {
        $this->authorize('view', $sourceControl);

        $this->validateRoute($project, $sourceControl);

        return new SourceControlResource($sourceControl);
    }

    #[Put('{sourceControl}', name: 'api.projects.source-controls.update', middleware: 'ability:write')]
    #[Endpoint(title: 'update')]
    #[BodyParam(name: 'name', description: 'The name of the storage provider.', required: true)]
    #[BodyParam(name: 'token', description: 'The token if provider requires api token')]
    #[BodyParam(name: 'url', description: 'The URL if the provider is Gitlab and it is self-hosted')]
    #[BodyParam(name: 'username', description: 'The username if the provider is Bitbucket')]
    #[BodyParam(name: 'password', description: 'The password if the provider is Bitbucket')]
    #[BodyParam(name: 'global', description: 'Accessible in all projects', enum: [true, false])]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class)]
    public function update(Request $request, Project $project, SourceControl $sourceControl)
    {
        $this->authorize('update', $sourceControl);

        $this->validateRoute($project, $sourceControl);

        $this->validate($request, EditSourceControl::rules($sourceControl, $request->all()));

        $sourceControl = app(EditSourceControl::class)->edit($sourceControl, $project, $request->all());

        return new SourceControlResource($sourceControl);
    }

    #[Delete('{sourceControl}', name: 'api.projects.source-controls.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete')]
    #[Response(status: 204)]
    public function delete(Project $project, SourceControl $sourceControl)
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
