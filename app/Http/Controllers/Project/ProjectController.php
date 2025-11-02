<?php

namespace App\Http\Controllers\Project;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\GetProjects;
use App\Actions\Projects\UpdateProject;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects')]
#[Middleware(['auth'])]
class ProjectController extends Controller
{
    #[Get('/', name: 'projects')]
    public function index(): Response
    {
        $this->authorize('viewAny', Project::class);

        return Inertia::render('projects/index', [
            'projects' => ProjectResource::collection(
                user()
                    ->allProjects()
                    ->with(['users'])
                    ->simplePaginate(20)
            ),
            'invitations' => ProjectUserResource::collection(
                UserProject::query()
                    ->where('email', user()->email)
                    ->whereNull('user_id')
                    ->simplePaginate(20)
            ),
        ]);
    }

    #[Get('/json', name: 'projects.json')]
    public function json(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $projects = app(GetProjects::class)->get(user(), $request->input(), 10);

        return ProjectResource::collection($projects);
    }

    #[Post('/', name: 'projects.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        app(CreateProject::class)->create(user(), $request->input());

        return redirect()->route('projects')
            ->with('success', __('Project created successfully.'));
    }

    #[Patch('/{project}', name: 'projects.update')]
    public function update(Project $project, Request $request): RedirectResponse
    {
        $this->authorize('update', $project);

        app(UpdateProject::class)->update($project, $request->input());

        return redirect()->route('projects')
            ->with('success', __('Project updated successfully.'));
    }

    #[Delete('{project}', name: 'projects.destroy')]
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        app(DeleteProject::class)->delete(user(), $project, $request->input());

        return redirect()->route('projects')
            ->with('success', __('Project deleted successfully.'));
    }
}
