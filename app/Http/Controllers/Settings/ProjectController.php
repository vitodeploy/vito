<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects')]
#[Middleware(['auth'])]
class ProjectController extends Controller
{
    #[Post('switch/{project}', name: 'projects.switch')]
    public function switch(Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        user()->update([
            'current_project_id' => $project->id,
        ]);

        return redirect()->route('servers');
    }
}
