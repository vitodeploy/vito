<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;

class Custom extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'custom';
    }

    public static function label(): string
    {
        return 'Custom';
    }

    public function webMode(): WebMode
    {
        return WebMode::PROXY;
    }
}
