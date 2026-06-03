<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum IpAddressFamily: string implements HasTableDisplay, VitoEnum
{
    case V4 = 'inet';
    case V6 = 'inet6';

    public function getColor(): string
    {
        return 'gray';
    }

    public function getText(): string
    {
        return match ($this) {
            self::V4 => 'IPv4',
            self::V6 => 'IPv6',
        };
    }
}
