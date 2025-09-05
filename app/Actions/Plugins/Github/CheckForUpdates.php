<?php

namespace App\Actions\Plugins\Github;

use App\Models\Plugin;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class CheckForUpdates
{
    public function __construct(
        private readonly GetReleaseInfo $getReleaseInfo
    ) {}

    public function handle(): void
    {
        $plugins = Plugin::whereNotNull('repo')->get();

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            try {
                [$username, $repo] = $this->parseGithubUrl($plugin->repo);
                $latestRelease = $this->getReleaseInfo->handle($username, $repo);
                if ($latestRelease === null) {
                    continue;
                }

                $plugin->updates_available = $latestRelease->tagName !== $plugin->version;
                $plugin->save();
            } catch (Exception $e) {
                Log::info('Failed to retrieve Plugin Updates: '.$e->getMessage());
            }
        }
    }

    /**
     * @throws Exception
     */
    private function parseGithubUrl(string $url): array
    {
        // TODO: Remove Duplication
        $parsed = parse_url(rtrim($url, '.git'));
        if (($parsed['host'] ?? '') !== 'github.com') {
            throw new Exception('Invalid GitHub URL provided');
        }

        $parts = explode('/', trim($parsed['path'], '/'));
        if (count($parts) < 2) {
            throw new Exception('Invalid GitHub repository URL format');
        }

        return [$parts[0], $parts[1]];
    }
}
