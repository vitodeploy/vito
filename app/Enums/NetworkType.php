<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum NetworkType: string implements HasTableDisplay, VitoEnum
{
    case PROVIDER = 'provider';
    case CUSTOM = 'custom';
    case WIREGUARD = 'wireguard';

    public function getColor(): string
    {
        return match ($this) {
            self::PROVIDER => 'info',
            self::CUSTOM => 'warning',
            self::WIREGUARD => 'success',
        };
    }

    public function getText(): string
    {
        return match ($this) {
            self::PROVIDER => 'provider',
            self::CUSTOM => 'custom',
            self::WIREGUARD => 'wireguard',
        };
    }
}
