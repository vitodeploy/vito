<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('projects.index');
    }

    public function switch($projectId)
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
