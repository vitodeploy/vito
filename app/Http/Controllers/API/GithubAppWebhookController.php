<?php

namespace App\Http\Controllers\API;

use App\Actions\GithubApp\HandlePushWebhook;
use App\Actions\GithubApp\ImportGithubAppInstallation;
use App\Http\Controllers\Controller;
use App\Models\GithubApp;
use App\Models\SourceControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\RouteAttributes\Attributes\Post;
use Throwable;

class GithubAppWebhookController extends Controller
{
    #[Post('api/webhooks/github-app', name: 'api.webhooks.github-app')]
    public function __invoke(Request $request): JsonResponse
    {
        $app = GithubApp::current();
        if (! $app) {
            abort(404);
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $raw = $request->getContent();

        if (! $this->verifySignature($raw, $signature, $app->webhook_secret)) {
            abort(401);
        }

        $event = (string) $request->header('X-GitHub-Event', '');
        $payload = $request->json()->all();
        $action = (string) ($payload['action'] ?? '');

        try {
            match ($event) {
                'installation' => $this->handleInstallation($action, $payload),
                'installation_repositories' => $this->bustRepoCache((int) ($payload['installation']['id'] ?? 0)),
                'push' => app(HandlePushWebhook::class)->handle($payload),
                default => null,
            };
        } catch (Throwable $e) {
            Log::error('GitHub App webhook handling failed', [
                'event' => $event,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleInstallation(string $action, array $payload): void
    {
        $installationId = (int) ($payload['installation']['id'] ?? 0);
        if ($installationId === 0) {
            return;
        }

        match ($action) {
            'created', 'unsuspend', 'new_permissions_accepted' => app(ImportGithubAppInstallation::class)
                ->import($installationId, $payload['installation'] ?? null),
            'deleted', 'suspend' => $this->deleteInstallation($installationId),
            default => null,
        };

        $this->bustRepoCache($installationId);
    }

    private function deleteInstallation(int $installationId): void
    {
        SourceControl::query()
            ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
            ->where('external_identifier', (string) $installationId)
            ->get()
            ->each(fn (SourceControl $sc) => $sc->delete());

        Cache::forget("github_app_token_{$installationId}");
        $this->bustRepoCache($installationId);
    }

    private function bustRepoCache(int $installationId): void
    {
        if ($installationId === 0) {
            return;
        }

        Cache::forget("github_app_repos_{$installationId}");
    }

    private function verifySignature(string $raw, string $signature, string $secret): bool
    {
        if ($signature === '' || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $raw, $secret);

        return hash_equals($expected, $signature);
    }
}
