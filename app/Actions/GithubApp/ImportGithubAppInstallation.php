<?php

namespace App\Actions\GithubApp;

use App\Models\GithubApp;
use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\GithubApp as GithubAppProvider;
use RuntimeException;

class ImportGithubAppInstallation
{
    /**
     * @param  array<string, mixed>|null  $installationData  Pre-fetched payload from GitHub
     */
    public function import(int $installationId, ?array $installationData = null): SourceControl
    {
        $app = GithubApp::current();
        if (! $app) {
            throw new RuntimeException('GitHub App is not configured.');
        }

        $installationData ??= $this->fetchInstallation($app, $installationId);

        $account = $installationData['account'] ?? [];
        $login = (string) ($account['login'] ?? '');
        $accountId = (int) ($account['id'] ?? 0);
        $accountType = (string) ($account['type'] ?? '');
        $htmlUrl = (string) ($installationData['html_url'] ?? '');

        /** @var SourceControl|null $existing */
        $existing = SourceControl::withTrashed()
            ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
            ->where('external_identifier', (string) $installationId)
            ->first();

        $providerData = [
            'account_login' => $login,
            'account_id' => $accountId,
            'account_type' => $accountType,
            'html_url' => $htmlUrl,
        ];

        if ($existing) {
            $existing->fill([
                'profile' => $login,
                'provider_data' => $providerData,
            ]);
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->save();

            return $existing;
        }

        $owner = User::query()->where('is_admin', true)->orderBy('id')->firstOrFail();

        return SourceControl::query()->create([
            'provider' => SourceControl::PROVIDER_GITHUB_APP,
            'profile' => $login,
            'user_id' => $owner->id,
            'project_id' => null,
            'external_identifier' => (string) $installationId,
            'provider_data' => $providerData,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchInstallation(GithubApp $app, int $installationId): array
    {
        $response = GithubAppProvider::appClient($app)
            ->get("https://api.github.com/app/installations/{$installationId}");

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch installation (HTTP '.$response->status().')');
        }

        return $response->json();
    }
}
