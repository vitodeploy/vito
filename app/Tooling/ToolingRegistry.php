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
}
