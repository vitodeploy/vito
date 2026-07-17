<?php

namespace App\Http\Controllers;

use App\Actions\Desktop\SetupDesktopAdmin;
use App\Actions\Desktop\VerifyDesktopUnlock;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('desktop')]
#[Middleware('desktop')]
class DesktopAuthController extends Controller
{
    private const LOCKED_USER_ID = 'desktop.locked_user_id';

    #[Get('/login', name: 'desktop.login', middleware: 'guest')]
    public function create(Request $request): Response
    {
        $lockedUserId = $this->lockedUserId($request);

        $users = User::query()->orderBy('name')->get();

        return Inertia::render('auth/desktop-login', [
            'users' => UserResource::collection($users),
            'setup_required' => $users->isEmpty(),
            'locked' => $lockedUserId !== null,
            'locked_user_id' => $lockedUserId,
        ]);
    }

    #[Post('/setup', name: 'desktop.setup', middleware: ['guest', 'throttle:desktop-auth'])]
    public function setup(Request $request, SetupDesktopAdmin $setupDesktopAdmin): RedirectResponse
    {
        abort_unless(User::query()->doesntExist(), 404);

        $user = $setupDesktopAdmin->create($request->all());

        Auth::login($user);

        $request->session()->forget(self::LOCKED_USER_ID);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    #[Post('/login/{user}', name: 'desktop.login.store', middleware: ['guest', 'throttle:desktop-auth'])]
    public function store(Request $request, User $user, VerifyDesktopUnlock $verifyDesktopUnlock): RedirectResponse
    {
        if ($this->lockedUserId($request) !== null) {
            $verifyDesktopUnlock->verify($user, $request->all());
        }

        Auth::login($user);

        $request->session()->forget(self::LOCKED_USER_ID);
        $request->session()->regenerate();

        $user->ensureHasDefaultProject();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    #[Post('/lock', name: 'desktop.lock', middleware: 'auth')]
    public function lock(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->migrate(true);
        $request->session()->put(self::LOCKED_USER_ID, $user->id);
        $request->session()->regenerateToken();

        return to_route('desktop.login');
    }

    private function lockedUserId(Request $request): ?int
    {
        $lockedUserId = $request->session()->get(self::LOCKED_USER_ID);

        if (! is_numeric($lockedUserId)) {
            return null;
        }

        $lockedUserId = (int) $lockedUserId;

        if (! User::query()->whereKey($lockedUserId)->exists()) {
            $request->session()->forget(self::LOCKED_USER_ID);

            return null;
        }

        return $lockedUserId;
    }
}
