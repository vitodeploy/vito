<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\UpdateProject;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('settings.projects.index', [
            'projects' => auth()->user()->projects,
        ]);
    }

    public function create(Request $request): HtmxResponse
    {
        app(CreateProject::class)->create($request->user(), $request->input());

        Toast::success('Project created.');

        return htmx()->redirect(route('projects'));
    }

    public function update(Request $request, Project $project): HtmxResponse
    {
        /** @var Project $project */
        $project = $request->user()->projects()->findOrFail($project->id);

        app(UpdateProject::class)->update($project, $request->input());

        Toast::success('Project updated.');

        return htmx()->redirect(route('projects'));
    }

    public function delete(Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Project $project */
        $project = $user->projects()->findOrFail($project->id);

        try {
            app(DeleteProject::class)->delete($user, $project);
        } catch (ValidationException $e) {
            Toast::error($e->getMessage());

            return back();
        }

        Toast::success('Project deleted.');

        return back();
    }

    public function switch($projectId): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Project $project */
        $project = $user->projects()->findOrFail($projectId);

        $user->current_project_id = $project->id;
        $user->save();

        return redirect()->route('servers');
    }
}
