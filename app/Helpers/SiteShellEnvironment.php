<?php

namespace App\Helpers;

use App\Models\Site;
use App\Tooling\ToolingRegistry;

final class SiteShellEnvironment
{
    /**
     * @return array<string, string>
     */
    public static function collect(Site $site): array
    {
        if (! $site->isolatedUser || $site->user === '' || $site->user === null) {
            return [];
        }

        $paths = [];
        foreach (ToolingRegistry::all() as $tool) {
            if ($tool->installedVersion($site) === null) {
                continue;
            }
            foreach ($tool->pathContributions($site) as $entry) {
                if ($entry !== '' && ! in_array($entry, $paths, true)) {
                    $paths[] = $entry;
                }
            }
        }

        if ($paths === []) {
            return [];
        }

        $base = "/usr/local/bin:/usr/bin:/bin:/home/{$site->user}/.local/bin";

        return ['PATH' => implode(':', $paths).':'.$base];
    }

    public static function wrap(Site $site, string $command, bool $cdToSitePath = false): string
    {
        $exports = '';
        foreach (self::collect($site) as $key => $value) {
            $exports .= sprintf('export %s=%s && ', $key, escapeshellarg($value));
        }

        $cd = $cdToSitePath && $site->path ? 'cd '.escapeshellarg($site->path).' && ' : '';

        return 'bash -c '.escapeshellarg($exports.$cd.$command);
    }
}
