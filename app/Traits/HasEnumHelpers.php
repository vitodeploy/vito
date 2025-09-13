<?php

namespace App\Traits;

use ReflectionClass;

trait HasEnumHelpers
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $reflection = new ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        return array_map(fn ($case) => $case->value, $constants);
    }

    /**
     * @return array<string>
     */
    public static function allValues(): array
    {
        $reflection = new ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        return array_values($constants);
    }
}
