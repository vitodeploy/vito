<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed all()
 */
class Plugins extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'plugins';
    }
}
