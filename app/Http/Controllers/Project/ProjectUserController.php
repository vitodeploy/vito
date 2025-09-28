<?php

namespace App\Http\Controllers\Project;

use App\Actions\Projects\InviteToProject;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects/{project}/users')]
#[Middleware(['auth'])]
class ProjectUserController extends Controller
{
    #[Post('/', name: 'projects.users.store')]
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        app(InviteToProject::class)->invite($project, $request->input());

        return back()->with('success', __('An invitation has been sent to the email address.'));
    }

    #[Delete('{email}', name: 'projects.users.destroy')]
    public function destroy(Project $project, string $email): RedirectResponse
    {
        $this->authorize('update', $project);

        /** @var ?User $user */
        $user = User::query()->where('email', $email)->first();

        if ($user && $project->role($user) === UserRole::OWNER) {
            return back()->with('error', __('You cannot remove the project owner.'));
        }

        if ($user?->id === user()->id) {
            return back()->with('error', __('You cannot remove yourself from the project.'));
        }

        $project->users()
            ->where('user_id', $user?->id)
            ->orWhere('email', $email)
            ->delete();

        return back()->with('success', __('The user has been removed.'));
    }
}
