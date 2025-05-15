<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('users')]
#[Middleware(['auth'])]
class UserController extends Controller
{
    #[Get('/', name: 'users')]
    public function index(Request $request): ResourceCollection
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
}
