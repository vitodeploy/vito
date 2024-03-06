<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void success(string $message)
 * @method static void error(string $message)
 * @method static void warning(string $message)
 * @method static void info(string $message)
 */
class Toast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'toast';
    }
}
