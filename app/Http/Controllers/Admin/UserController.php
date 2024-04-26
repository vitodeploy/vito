<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
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

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request): HtmxResponse
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => [
                'required',
                Rule::in([UserRole::ADMIN, UserRole::USER]),
            ],
        ]);

        $user = User::query()->create(array_merge(
            $request->only('name', 'email', 'role'),
            [
                'password' => bcrypt($request->password),
                'timezone' => 'UTC',
            ]
        ));

        return htmx()->redirect(route('admin.users.show', $user));
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user,
            'projects' => Project::query()->get(),
        ]);
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'timezone' => [
                'required',
                Rule::in(timezone_identifiers_list()),
            ],
            'role' => [
                'required',
                Rule::in([UserRole::ADMIN, UserRole::USER]),
                function ($attribute, $value, $fail) use ($user, $request) {
                    if ($user->is($request->user()) && $value !== $user->role) {
                        $fail('You cannot change your own role');
                    }
                },
            ],
        ]);

        $user->update($request->only('name', 'email', 'timezone', 'role'));

        Toast::success('User updated successfully');

        return back();
    }

    public function updateProjects(User $user, Request $request): RedirectResponse
    {
        $this->validate($request, [
            'projects.*' => [
                'required',
                Rule::exists('projects', 'id'),
            ],
        ]);

        $user->projects()->sync($request->projects);

        Toast::success('Projects updated successfully');

        return back();
    }
}
