<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GithubApp\CreateGithubAppFromManifest;
use App\Actions\GithubApp\CreateGithubAppManually;
use App\Actions\GithubApp\ImportGithubAppInstallation;
use App\Actions\GithubApp\RemoveGithubApp;
use App\Actions\GithubApp\SyncGithubAppInstallations;
use App\Http\Controllers\Controller;
use App\Http\Resources\SourceControlResource;
use App\Models\GithubApp;
use App\Models\SourceControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('admin/github-app')]
#[Middleware(['auth', 'must-be-admin'])]
class GithubAppController extends Controller
{
    #[Get('/', name: 'github-app')]
    public function index(Request $request): Response
    {
        $app = GithubApp::current();

        $installations = SourceControl::query()
            ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
            ->orderBy('profile')
            ->get();

        $manifestJson = json_encode($this->buildManifest());
        $appUrl = rtrim((string) config('app.url'), '/');

        $pendingWebhookSecret = $app ? null : $this->pendingWebhookSecret($request);

        return Inertia::render('github-app/index', [
            'githubApp' => $app ? [
                'app_id' => $app->app_id,
                'app_slug' => $app->app_slug,
                'name' => $app->name,
                'html_url' => $app->html_url,
                'created_at' => $app->created_at,
            ] : null,
            'manifest' => [
                'manifest' => $manifestJson,
                'submit_url' => 'https://github.com/settings/apps/new',
            ],
            'manualSetup' => [
                'create_url' => 'https://github.com/settings/apps/new',
                'webhook_url' => $appUrl.'/api/webhooks/github-app',
                'homepage_url' => $appUrl,
                'callback_url' => route('github-app.install-callback'),
                'setup_url' => route('github-app.install-callback'),
                'webhook_secret' => $pendingWebhookSecret,
            ],
            'installPath' => $app ? "https://github.com/apps/{$app->app_slug}/installations/new" : null,
            'installations' => SourceControlResource::collection($installations),
            'localUrlWarning' => $this->isLocalUrl(),
        ]);
    }

    #[Get('/manifest-callback', name: 'github-app.manifest-callback')]
    public function manifestCallback(Request $request, CreateGithubAppFromManifest $action): RedirectResponse
    {
        $code = (string) $request->query('code');

        if ($code === '') {
            return to_route('github-app')->with('error', __('GitHub did not return a manifest code.'));
        }

        try {
            $action->create($code);
        } catch (\Throwable $e) {
            Log::error('GitHub App manifest conversion failed', ['error' => $e->getMessage()]);

            return to_route('github-app')->with('error', __('Failed to create GitHub App: :error', ['error' => $e->getMessage()]));
        }

        return to_route('github-app')->with('success', __('GitHub App created successfully.'));
    }

    #[Post('/manual', name: 'github-app.manual')]
    public function manual(Request $request, CreateGithubAppManually $action): RedirectResponse
    {
        $action->create($request->all());

        $request->session()->forget('github_app_pending_webhook_secret');

        return to_route('github-app')->with('success', __('GitHub App configured.'));
    }

    private function pendingWebhookSecret(Request $request): string
    {
        $secret = $request->session()->get('github_app_pending_webhook_secret');
        if (! is_string($secret) || $secret === '') {
            $secret = Str::random(40);
            $request->session()->put('github_app_pending_webhook_secret', $secret);
        }

        return $secret;
    }

    #[Get('/install', name: 'github-app.install')]
    public function install(): RedirectResponse
    {
        $app = GithubApp::current();
        if (! $app) {
            return to_route('github-app')->with('error', __('GitHub App is not configured.'));
        }

        return redirect("https://github.com/apps/{$app->app_slug}/installations/new");
    }

    #[Get('/install-callback', name: 'github-app.install-callback')]
    public function installCallback(Request $request, ImportGithubAppInstallation $action): RedirectResponse
    {
        $installationId = (int) $request->query('installation_id');
        if ($installationId === 0) {
            return to_route('source-controls')->with('error', __('Missing installation_id from GitHub.'));
        }

        $action->import($installationId);

        return to_route('source-controls')->with('success', __('GitHub App installation connected.'));
    }

    #[Post('/sync', name: 'github-app.sync')]
    public function sync(SyncGithubAppInstallations $action): RedirectResponse
    {
        $result = $action->run();

        return to_route('github-app')->with(
            'success',
            __('Sync complete. :added added, :removed removed, :kept kept.', $result)
        );
    }

    #[Delete('/', name: 'github-app.destroy')]
    public function destroy(RemoveGithubApp $action): RedirectResponse
    {
        $action->remove();

        return to_route('github-app')->with('success', __('GitHub App removed.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildManifest(): array
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'vito';

        return [
            'name' => "Vito ({$host})",
            'url' => $appUrl,
            'hook_attributes' => [
                'url' => $appUrl.'/api/webhooks/github-app',
            ],
            'redirect_url' => route('github-app.manifest-callback'),
            'callback_urls' => [route('github-app.install-callback')],
            'setup_url' => route('github-app.install-callback'),
            'setup_on_update' => true,
            'public' => true,
            'default_permissions' => [
                'contents' => 'read',
                'metadata' => 'read',
            ],
            'default_events' => [
                'push',
            ],
        ];
    }

    private function isLocalUrl(): bool
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';
        if ($host === '') {
            return true;
        }

        return $host === 'localhost'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_starts_with($host, '127.')
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host) === 1;
    }
}
