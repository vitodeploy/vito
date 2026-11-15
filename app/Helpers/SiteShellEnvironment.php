<?php

namespace App\Helpers;

use App\DTOs\ToolingTargetDTO;
use App\Models\Site;
use App\Tooling\TargetToolingInterface;
use App\Tooling\ToolingRegistry;

final class SiteShellEnvironment
{
    /**
     * @return array<string, string>
     */
    public static function collect(Site|ToolingTargetDTO $target): array
    {
        $site = $target instanceof Site ? $target : null;
        $target = $target instanceof Site ? ToolingTargetDTO::forSite($target) : $target;
        if (! $target->isolatedUser || $target->user === '') {
            return [];
        }

        $paths = [];
        foreach (ToolingRegistry::all() as $tool) {
            if ($site) {
                $version = $tool->installedVersion($site);
                $contributions = $version === null ? [] : $tool->pathContributions($site);
            } elseif ($tool instanceof TargetToolingInterface) {
                $version = $tool->installedVersionForTarget($target);
                $contributions = $version === null ? [] : $tool->pathContributionsForTarget($target);
            } else {
                continue;
            }
            foreach ($contributions as $entry) {
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
        foreach (self::collect($site) as $key => $value) {
            $exports .= sprintf('export %s=%s && ', $key, escapeshellarg($value));
        }

        $cd = $cdToSitePath && $site->path ? 'cd '.escapeshellarg($site->path).' && ' : '';

        return 'bash -c '.escapeshellarg($exports.$cd.$command);
    }
}
