<?php

namespace App\Http\Controllers\Settings;

use App\Actions\User\CreateUser;
use App\Actions\User\UpdateUser;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->paginate(20);

        return view('settings.users.index', compact('users'));
    }

    public function store(Request $request): HtmxResponse
    {
        $user = app(CreateUser::class)->create($request->input());

        return htmx()->redirect(route('settings.users.show', $user));
    }

    public function show(User $user): View
    {
        return view('settings.users.show', [
            'user' => $user,
        ]);
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        app(UpdateUser::class)->update($user, $request->input());

        Toast::success('User updated successfully');

        return back();
    }

    public function updateProjects(User $user, Request $request): HtmxResponse
    {
        $this->validate($request, [
            'projects.*' => [
                'required',
                Rule::exists('projects', 'id'),
            ],
        ]);

        $user->projects()->sync($request->projects);

        if ($user->currentProject && ! $user->projects->contains($user->currentProject)) {
            $user->current_project_id = null;
            $user->save();
        }

        /** @var Project $firstProject */
        $firstProject = $user->projects->first();
        if (! $user->currentProject && $firstProject) {
            $user->current_project_id = $firstProject->id;
            $user->save();
        }

        Toast::success('Projects updated successfully');

        return htmx()->redirect(route('settings.users.show', $user));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(request()->user())) {
            Toast::error('You cannot delete your own account');

            return back();
        }

        $user->delete();

        Toast::success('User deleted successfully');

        return redirect()->route('settings.users.index');
    }
}
