<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;

class StaticSite extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'static';
    }

    public static function label(): string
    {
        return 'Static';
    }

    public function webMode(): WebMode
    {
        return WebMode::STATIC;
    }
}
