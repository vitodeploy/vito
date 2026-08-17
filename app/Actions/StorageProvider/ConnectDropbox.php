<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use App\Models\User;
use App\StorageProviders\Dropbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ConnectDropbox
{
    private const string AUTHORIZE_URL = 'https://www.dropbox.com/oauth2/authorize';

    private const string TOKEN_URL = 'https://api.dropbox.com/oauth2/token';

    private const string SESSION_KEY = 'dropbox_oauth';

    /**
     * @param  array<string, mixed>  $input
     * @param  ?int  $projectId  The project the provider will belong to, or null for a global one.
     *
     * @throws ValidationException
     */
    public function redirectUrl(array $input, ?int $projectId): string
    {
        Validator::make($input, [
            'name' => ['required'],
            'app_key' => ['required'],
            'app_secret' => ['required'],
        ])->validate();

        $state = Str::random(40);

        session()->put(self::SESSION_KEY, [
            'state' => $state,
            'name' => $input['name'],
            'app_key' => $input['app_key'],
            'app_secret' => $input['app_secret'],
            'project_id' => $projectId,
        ]);

        return self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => $input['app_key'],
            'response_type' => 'code',
            'token_access_type' => 'offline',
            'state' => $state,
            'redirect_uri' => $this->redirectUri(),
        ]);
    }

    /**
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function handleCallback(User $user, Request $request): StorageProvider
    {
        if ($request->filled('error')) {
            session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'provider' => __('Dropbox authorization was cancelled.'),
            ]);
        }

        /** @var array<string, mixed>|null $pending */
        $pending = session()->pull(self::SESSION_KEY);

        if (! is_array($pending) || ! hash_equals((string) ($pending['state'] ?? ''), (string) $request->query('state'))) {
            throw ValidationException::withMessages([
                'provider' => __('Invalid Dropbox authorization state.'),
            ]);
        }

        Validator::make($request->query(), [
            'code' => ['required', 'string'],
        ])->validate();

        $refreshToken = $this->exchangeCode(
            (string) $request->query('code'),
            (string) $pending['app_key'],
            (string) $pending['app_secret'],
        );

        /** @var ?int $projectId */
        $projectId = $pending['project_id'] ?? null;

        return app(CreateStorageProvider::class)->create($user, [
            'provider' => Dropbox::id(),
            'name' => $pending['name'],
            'app_key' => $pending['app_key'],
            'app_secret' => $pending['app_secret'],
            'refresh_token' => $refreshToken,
        ], $projectId);
    }

    public function redirectUri(): string
    {
        return route('storage-providers.dropbox.callback');
    }

    private function exchangeCode(string $code, string $appKey, string $appSecret): string
    {
        $res = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $appKey,
            'client_secret' => $appSecret,
            'redirect_uri' => $this->redirectUri(),
        ]);

        if (! $res->successful()) {
            throw new RuntimeException("Failed to exchange Dropbox authorization code (HTTP {$res->status()})");
        }

        $refreshToken = $res->json('refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new RuntimeException('Dropbox did not return a refresh token. Ensure the app uses offline access.');
        }

        return $refreshToken;
    }
}
