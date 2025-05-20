<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiKeyResource;
use App\Models\PersonalAccessToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/api-keys')]
#[Middleware(['auth'])]
class ApiKeyController extends Controller
{
    #[Get('/', name: 'api-keys')]
    public function index(): Response
    {
        $this->authorize('viewAny', PersonalAccessToken::class);

        return Inertia::render('api-keys/index', [
            'apiKeys' => ApiKeyResource::collection(user()->tokens()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'api-keys.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PersonalAccessToken::class);

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'permission' => 'required|in:read,write',
        ]);

        $permissions = ['read'];
        if ($request->input('permission') === 'write') {
            $permissions[] = 'write';
        }
        $token = user()->createToken($request->input('name'), $permissions);

        return back()
            ->with('success', 'Api key created.')
            ->with('data', [
                'token' => $token->plainTextToken,
            ]);
    }

    #[Delete('/{apiKey}', name: 'api-keys.destroy')]
    public function destroy(PersonalAccessToken $apiKey): RedirectResponse
    {
        $this->authorize('delete', $apiKey);

        $apiKey->delete();

        return back()->with('success', 'Api Key deleted.');
    }
}
