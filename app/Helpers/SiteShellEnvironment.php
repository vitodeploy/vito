<?php

namespace App\Helpers;

use App\DTOs\ToolingTargetDTO;
use App\Models\Site;
use App\Tooling\ToolingRegistry;

final class SiteShellEnvironment
{
    /**
     * @return array<string, string>
     */
    public static function collect(ToolingTargetDTO $target): array
    {
        if (! $target->isolatedUser || $target->user === '') {
            return [];
        }

        $paths = [];
        foreach (ToolingRegistry::all() as $tool) {
            if ($tool->installedVersion($target) === null) {
                continue;
            }
            foreach ($tool->pathContributions($target) as $entry) {
                if ($entry !== '' && ! in_array($entry, $paths, true)) {
                    $paths[] = $entry;
                }
            }
        }

        if ($paths === []) {
            return [];
        }

        $base = "/usr/local/bin:/usr/bin:/bin:/home/{$target->user}/.local/bin";

        return ['PATH' => implode(':', $paths).':'.$base];
    }

    public static function wrap(Site $site, string $command, bool $cdToSitePath = false): string
    {
        $exports = '';
        foreach (self::collect(ToolingTargetDTO::forSite($site)) as $key => $value) {
            $exports .= sprintf('export %s=%s && ', $key, escapeshellarg($value));
        }

        $cd = $cdToSitePath && $site->path ? 'cd '.escapeshellarg($site->path).' && ' : '';

        return 'bash -c '.escapeshellarg($exports.$cd.$command);
    }
}
