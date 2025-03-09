<?php

namespace App\Traits;

trait Enum
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);

        return $reflection->getConstants();
    }
}
