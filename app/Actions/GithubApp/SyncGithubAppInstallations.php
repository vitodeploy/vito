<?php

namespace App\Actions\GithubApp;

use App\Models\GithubApp;
use App\Models\SourceControl;
use App\SourceControlProviders\GithubApp as GithubAppProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncGithubAppInstallations
{
    /**
     * @return array{added: int, removed: int, kept: int}
     */
    public function run(): array
    {
        $app = GithubApp::current();
        if (! $app) {
            return ['added' => 0, 'removed' => 0, 'kept' => 0];
        }

        $remote = $this->fetchAllInstallations($app);
        $remoteIds = collect($remote)->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $importer = app(ImportGithubAppInstallation::class);
        $added = 0;
        $kept = 0;

        foreach ($remote as $installation) {
            $id = (int) ($installation['id'] ?? 0);
            if ($id === 0) {
                continue;
            }

            $existed = SourceControl::query()
                ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
                ->where('external_identifier', (string) $id)
                ->exists();

            try {
                $importer->import($id, $installation);
                $existed ? $kept++ : $added++;
            } catch (\Throwable $e) {
                Log::error('Failed to import GitHub App installation', [
                    'installation_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $stale = SourceControl::query()
            ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
            ->whereNotNull('external_identifier')
            ->when(
                $remoteIds !== [],
                fn ($q) => $q->whereNotIn('external_identifier', $remoteIds)
            )
            ->get();

        $removed = 0;
        foreach ($stale as $sourceControl) {
            $installationId = (int) $sourceControl->external_identifier;
            $sourceControl->delete();
            Cache::forget("github_app_token_{$installationId}");
            Cache::forget("github_app_repos_{$installationId}");
            $removed++;
        }

        return ['added' => $added, 'removed' => $removed, 'kept' => $kept];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllInstallations(GithubApp $app): array
    {
        $installations = [];
        $page = 1;

        while (true) {
            $res = GithubAppProvider::appClient($app)->get('https://api.github.com/app/installations', [
                'per_page' => 100,
                'page' => $page,
            ]);

            if (! $res->successful()) {
                throw new RuntimeException('Failed to list installations (HTTP '.$res->status().')');
            }

            $body = $res->json();
            if (! is_array($body) || $body === []) {
                break;
            }

            $installations = array_merge($installations, $body);
            if (count($body) < 100) {
                break;
            }
            $page++;
            if ($page > 25) {
                break;
            }
        }

        return $installations;
    }
}
