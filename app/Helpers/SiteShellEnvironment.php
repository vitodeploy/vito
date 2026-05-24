<?php

namespace App\Helpers;

use App\Models\Site;
use App\Tooling\ToolingRegistry;

/**
 * Builds the shell environment for commands Vito runs against a site's
 * isolated user, by collecting PATH contributions from every registered
 * tool that's currently installed.
 *
 * Use `collect()` to obtain the env vars (suitable for `$ssh->setVariables(...)`
 * or supervisor's `environment=` line), or `wrap()` to produce a single
 * `bash -c '…'` string with the env exports + optional `cd`.
 */
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
