<?php

namespace App\Traits;

use ReflectionClass;

trait Enum
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $reflection = new ReflectionClass(self::class);

        return $reflection->getConstants();
    }
}
