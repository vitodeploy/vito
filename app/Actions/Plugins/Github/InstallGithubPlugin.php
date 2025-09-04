<?php

namespace App\Actions\Plugins\Github;

use App\Actions\Plugins\InstallPlugin;
use App\Models\Plugin;
use Exception;
use File;

final readonly class InstallGithubPlugin
{
    public function __construct(
        private GetReleaseInfo $releaseInfo,
        private DownloadRelease $downloadRelease,
        private ExtractPlugin $extractZip,
        private InstallPlugin $installPlugin,
    ) {}

    /**
     * @throws Exception
     */
    public function handle(string $url, ?Plugin $plugin = null): Plugin
    {
        [$username, $repo] = $this->parseGitHubUrl($url);

        $release = $this->releaseInfo->handle($username, $repo);
        if ($release === null) {
            throw new Exception('No Released Versions');
        }

        $psrUser = $this->toPsrCase($username);
        $psrRepo = $this->toPsrCase($repo);

        $folder = implode(DIRECTORY_SEPARATOR, [$psrUser, $psrRepo]);
        $pluginsFolder = implode(DIRECTORY_SEPARATOR, ['Plugins', $folder]);
        $zipFile = implode(DIRECTORY_SEPARATOR, ['app', 'temp', "$repo.zip"]);

        $zipLocation = storage_path($zipFile);
        $extractLocation = app_path($pluginsFolder);

        $this->downloadRelease->handle($release, $zipLocation);
        $this->extractZip->handle($zipLocation, $extractLocation);

        File::delete($zipLocation);

        if ($plugin === null) {
            $plugin = Plugin::create([
                'repo' => $url,
                'folder' => $folder,
                'version' => $release->tagName,
                'namespace' => "App\\Plugins\\$psrUser\\$psrRepo\\Plugin",
            ]);

            $this->installPlugin->handle($plugin);
        } else {
            $plugin->version = $release->tagName;
            $plugin->updates_available = false;
            $plugin->save();
        }

        return $plugin;
    }

    /**
     * @throws Exception
     */
    private function parseGithubUrl(string $url): array
    {
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

    private function toPsrCase(string $string): string
    {
        $string = str_replace(['-', '_', ' '], ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);

        return ucfirst($string);
    }
}
