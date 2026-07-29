<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum NetworkAddressingPool: string implements HasTableDisplay, VitoEnum
{
    case CGNAT = 'cgnat';
    case RFC1918 = 'rfc1918';

    public function getColor(): string
    {
        return match ($this) {
            self::CGNAT => 'info',
            self::RFC1918 => 'warning',
        };
    }

    public function getText(): string
    {
        return match ($this) {
            self::CGNAT => 'CGNAT (100.64.0.0/10)',
            self::RFC1918 => 'Private (RFC1918)',
        };
    }
}
