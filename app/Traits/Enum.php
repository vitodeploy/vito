<?php

namespace App\Traits;

trait Enum
{
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);

        return $reflection->getConstants();
    }
}
