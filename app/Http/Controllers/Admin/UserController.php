<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
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
        return view('admin.users.show', compact('user'));
    }
}
