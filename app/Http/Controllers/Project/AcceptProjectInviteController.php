<?php

namespace App\Http\Controllers\Project;

use App\Actions\Projects\AcceptProjectInvitation;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects')]
#[Middleware(['auth'])]
class AcceptProjectInviteController extends Controller
{
    #[Get('/{project}/invitations/{token}', name: 'projects.invitations.show')]
    public function show(Project $project, string $token): Response
    {
        $invitation = app(AcceptProjectInvitation::class)->find($project, user(), $token);

        return Inertia::render('projects/accept-invitation', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'role' => $invitation->role->getText(),
            'token' => $token,
        ]);
    }

    #[Post('/{project}/invitations/{token}', name: 'projects.invitations.accept')]
    public function accept(Project $project, string $token): RedirectResponse
    {
        app(AcceptProjectInvitation::class)->accept($project, user(), $token);

        return redirect()->route('projects')->with('success', __('You joined the project successfully.'));
    }
}
