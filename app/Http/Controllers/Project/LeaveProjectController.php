<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects')]
#[Middleware(['auth'])]
class LeaveProjectController extends Controller
{
    #[Delete('/{project}/leave', name: 'projects.leave')]
    public function __invoke(Project $project): RedirectResponse
    {
        /** @var ?UserProject $user */
        $user = $project->users()
            ->where('user_id', user()->id)
            ->orWhere('email', user()->email)
            ->first();
        if (! $user) {
            abort(404);
        }

        $user->delete();

        return back()->with('success', __('You left the project successfully.'));
    }
}
