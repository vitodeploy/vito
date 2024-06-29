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
        $this->authorize('viewAny', Project::class);

        return view('settings.projects.index', [
            'projects' => Project::all(),
        ]);
    }

    public function create(Request $request): HtmxResponse
    {
        $this->authorize('create', Project::class);

        app(CreateProject::class)->create($request->user(), $request->input());

        Toast::success('Project created.');

        return htmx()->redirect(route('settings.projects'));
    }

    public function update(Request $request, Project $project): HtmxResponse
    {
        $this->authorize('update', $project);

        app(UpdateProject::class)->update($project, $request->input());

        Toast::success('Project updated.');

        return htmx()->redirect(route('settings.projects'));
    }

    public function delete(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        /** @var User $user */
        $user = auth()->user();

        try {
            app(DeleteProject::class)->delete($user, $project);
        } catch (ValidationException $e) {
            Toast::error($e->getMessage());

            return back();
        }

        Toast::success('Project deleted.');

        return back();
    }

    public function switch(Request $request, $projectId): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Project $project */
        $project = $user->projects()->findOrFail($projectId);

        $this->authorize('view', $project);

        $user->current_project_id = $project->id;
        $user->save();

        // check if the referer is settings/*
        if (str_contains($request->headers->get('referer'), 'settings')) {
            return redirect()->to($request->headers->get('referer'));
        }

        return redirect()->route('servers');
    }
}
