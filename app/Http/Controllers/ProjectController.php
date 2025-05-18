<?php

namespace App\Http\Controllers;

use App\Actions\Projects\AddUser;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\UpdateProject;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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
                Project::query()->with('users')->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Post('/', name: 'projects.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = app(CreateProject::class)->create(user(), $request->all());

        user()->update([
            'current_project_id' => $project->id,
        ]);

        return redirect()->route('projects')
            ->with('success', 'Project created successfully.');
    }

    #[Patch('/{project}', name: 'projects.update')]
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        app(UpdateProject::class)->update($project, $request->all());

        return redirect()->route('projects')
            ->with('success', 'Project updated successfully.');
    }

    #[Patch('switch/{project}', name: 'projects.switch')]
    public function switch(Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        user()->update([
            'current_project_id' => $project->id,
        ]);

        $previousUrl = URL::previous();
        $previousRequest = Request::create($previousUrl);
        $previousRoute = app('router')->getRoutes()->match($previousRequest);

        if (count($previousRoute->parameters()) > 0) {
            return redirect()->route('servers');
        }

        return redirect()->route($previousRoute->getName());
    }

    #[Post('/{project}/users', name: 'projects.users.store')]
    public function storeUser(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        app(AddUser::class)->add($project, $request->all());

        return redirect()->route('projects')
            ->with('success', 'User added to project successfully.');
    }

    #[Delete('{project}/users/{user}', name: 'projects.users.destroy')]
    public function destroyUser(Project $project, User $user): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->users()->detach($user);

        return redirect()->route('projects')
            ->with('success', 'User removed from project successfully.');
    }

    #[Delete('{project}', name: 'projects.destroy')]
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        app(DeleteProject::class)->delete(user(), $project, $request->all());

        return redirect()->route('projects')
            ->with('success', 'Project deleted successfully.');
    }
}
