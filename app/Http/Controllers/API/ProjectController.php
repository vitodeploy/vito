<?php

namespace App\Http\Controllers\API;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\UpdateProject;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

#[Middleware('auth:sanctum')]
class ProjectController extends Controller
{
    #[Get('api/projects', name: 'api.projects.index', middleware: 'ability:read')]
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $projects = user()->projects();

        $token = $this->getToken();
        if ($token) {
            $scopedProjectIds = $token->getProjectIds();
            if (! empty($scopedProjectIds)) {
                $projects->whereIn('projects.id', $scopedProjectIds);
            }
        }

        return ProjectResource::collection($projects->get());
    }

    #[Post('api/projects', name: 'api.projects.create', middleware: 'ability:write')]
    public function create(Request $request): ProjectResource
    {
        $this->authorize('create', Project::class);

        $project = app(CreateProject::class)->create(user(), $request->all());

        return new ProjectResource($project);
    }

    #[Get('api/projects/{project}', name: 'api.projects.show', middleware: 'ability:read')]
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        $token = $this->getToken();
        if ($token && ! $token->hasProjectAccess($project)) {
            abort(403, 'This token does not have access to this project.');
        }

        return new ProjectResource($project);
    }

    #[Put('api/projects/{project}', name: 'api.projects.update', middleware: 'ability:write')]
    public function update(Request $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $token = $this->getToken();
        if ($token && ! $token->hasProjectAccess($project)) {
            abort(403, 'This token does not have access to this project.');
        }

        $project = app(UpdateProject::class)->update($project, $request->all());

        return new ProjectResource($project);
    }

    #[Delete('api/projects/{project}', name: 'api.projects.delete', middleware: 'ability:write')]
    public function delete(Project $project): Response
    {
        $this->authorize('delete', $project);

        $token = $this->getToken();
        if ($token && ! $token->hasProjectAccess($project)) {
            abort(403, 'This token does not have access to this project.');
        }

        app(DeleteProject::class)->delete(user(), $project, [
            'name' => $project->name,
        ]);

        return response()->noContent();
    }

    private function getToken(): ?PersonalAccessToken
    {
        $token = user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->exists) {
            return $token;
        }

        return null;
    }
}
