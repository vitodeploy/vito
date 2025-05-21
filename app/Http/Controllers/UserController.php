<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUser;
use App\Actions\User\UpdateUser;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/users')]
#[Middleware(['auth'])]
class UserController extends Controller
{
    #[Get('/', name: 'users')]
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('users/index', [
            'users' => UserResource::collection(
                User::query()->with('projects')->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Get('/json', name: 'users.json')]
    public function json(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $this->validate($request, [
            'query' => [
                'nullable',
                'string',
            ],
        ]);

        $users = User::query()->where('name', 'like', "%{$request->input('query')}%")
            ->orWhere('email', 'like', "%{$request->input('query')}%")
            ->take(10)
            ->get();

        return UserResource::collection($users);
    }

    #[Post('/', name: 'users.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        app(CreateUser::class)->create($request->all());

        return to_route('users')->with('success', 'User created successfully.');
    }

    #[Patch('/{user}', name: 'users.update')]
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        app(UpdateUser::class)->update($user, $request->all());

        return to_route('users')->with('success', 'User updated successfully.');
    }

    #[Post('/{user}/projects', name: 'users.projects.store')]
    public function addToProject(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->validate($request, [
            'project' => [
                'required',
                Rule::exists('projects', 'id'),
            ],
        ]);

        $project = Project::query()->findOrFail($request->input('project'));

        $user->projects()->detach($project);
        $user->projects()->attach($project);

        return to_route('users')->with('success', 'Project was successfully added to user.');
    }

    #[Delete('/{user}/projects/{project}', name: 'users.projects.destroy')]
    public function removeProject(User $user, Project $project): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->projects()->detach($project);

        return to_route('users')->with('success', 'Project was successfully removed from user.');
    }

    #[Delete('/{user}', 'users.destroy')]
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return to_route('users')->with('success', 'User was successfully deleted.');
    }
}
