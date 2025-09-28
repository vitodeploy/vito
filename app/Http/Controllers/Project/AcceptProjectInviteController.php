<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects')]
#[Middleware(['auth'])]
class AcceptProjectInviteController extends Controller
{
    #[Get('/{project}/invitations/accept', name: 'projects.invitations.accept')]
    public function __invoke(Project $project): RedirectResponse
    {
        /** @var ?UserProject $user */
        $user = $project->users()->where('email', user()->email)->first();
        if (! $user) {
            abort(404);
        }

        $user->email = null;
        $user->user_id = user()->id;
        $user->save();

        return redirect()->route('projects')->with('success', __('You joined the project successfully.'));
    }
}
