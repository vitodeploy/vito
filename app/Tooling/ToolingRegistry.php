<?php

namespace App\Tooling;

final class ToolingRegistry
{
    /**
     * @return array<string, ToolingInterface>
     */
    public static function all(): array
    {
        /** @var array<int, class-string<ToolingInterface>> $providers */
        $providers = config('tooling.providers', []);

        $out = [];
        foreach ($providers as $class) {
            /** @var ToolingInterface $instance */
            $instance = new $class;
            $out[$class::id()] = $instance;
        }

        return $out;
    }

    public static function find(string $id): ?ToolingInterface
    {
        return self::all()[$id] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function ids(): array
    {
        return array_keys(self::all());
    }

    /**
     * Does the given shell `$command` reference any of the named tool's
     * commands? Token-based heuristic with symmetric shell-separator
     * boundaries — `cd x && node app.js` matches `node`, but `php run-node.sh`
     * and `nodejs app.js` do not.
     */
    public static function commandReferences(string $command, string $toolId): bool
    {
        $tool = self::find($toolId);
        if (! $tool || $tool::commands() === []) {
            return false;
        }

        $alts = implode('|', array_map('preg_quote', $tool::commands()));
        $boundary = '[\s;&|`()]';
        $pattern = "/(^|$boundary)($alts)($boundary|$)/";

        return preg_match($pattern, $command) === 1;
    }
}
