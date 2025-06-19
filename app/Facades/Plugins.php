<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed all()
 * @method static string install(string $url, ?string $branch = null, ?string $tag = null)
 * @method static string load()
 * @method static string uninstall(string $name)
 * @method static void cleanup()
 */
class Plugins extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'plugins';
    }
}
