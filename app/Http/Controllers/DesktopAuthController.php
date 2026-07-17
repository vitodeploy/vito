<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\DesktopRuntime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('desktop')]
class DesktopAuthController extends Controller
{
    private const LOCKED_USER_ID = 'desktop.locked_user_id';

    private const LOCKED_AT = 'desktop.locked_at';

    #[Get('/login', name: 'desktop.login', middleware: 'guest')]
    public function create(Request $request): Response
    {
        abort_unless(DesktopRuntime::enabled(), 404);

        $lockedUserId = $this->lockedUserId($request);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

        return Inertia::render('auth/desktop-login', [
            'users' => $users,
            'setup_required' => $users->isEmpty(),
            'locked' => $lockedUserId !== null,
            'locked_user_id' => $lockedUserId,
        ]);
    }

    #[Post('/setup', name: 'desktop.setup', middleware: 'guest')]
    public function setup(Request $request): RedirectResponse
    {
        abort_unless(DesktopRuntime::enabled(), 404);
        abort_unless(User::query()->doesntExist(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'timezone' => 'UTC',
            'is_admin' => true,
        ]);

        $user->ensureHasDefaultProject();

        Auth::login($user);

        $request->session()->forget([self::LOCKED_USER_ID, self::LOCKED_AT]);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    #[Post('/login/{user}', name: 'desktop.login.store', middleware: 'guest')]
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless(DesktopRuntime::enabled(), 404);

        if ($this->lockedUserId($request) !== null) {
            $validated = $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => __('auth.password'),
                ]);
            }
        }

        Auth::login($user);

        $request->session()->forget([self::LOCKED_USER_ID, self::LOCKED_AT]);
        $request->session()->regenerate();

        $user->ensureHasDefaultProject();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    #[Post('/lock', name: 'desktop.lock', middleware: 'auth')]
    public function lock(Request $request): RedirectResponse
    {
        abort_unless(DesktopRuntime::enabled(), 404);

        /** @var User $user */
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->migrate(true);
        $request->session()->put(self::LOCKED_USER_ID, $user->id);
        $request->session()->put(self::LOCKED_AT, now()->timestamp);
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
            $request->session()->forget([self::LOCKED_USER_ID, self::LOCKED_AT]);

            return null;
        }

        return $lockedUserId;
    }
}
