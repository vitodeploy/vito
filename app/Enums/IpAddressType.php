<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum IpAddressType: string implements HasTableDisplay, VitoEnum
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case UNKNOWN = 'unknown';

    public function getColor(): string
    {
        return match ($this) {
            self::PUBLIC => 'info',
            self::PRIVATE => 'warning',
            self::UNKNOWN => 'gray',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
