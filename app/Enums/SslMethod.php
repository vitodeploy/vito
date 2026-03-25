<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use App\Traits\HasEnumHelpers;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum SslMethod: string implements HasTableDisplay, VitoEnum
{
    use HasEnumHelpers;

    case NONE = 'none';
    case LETSENCRYPT = 'letsencrypt';
    case CUSTOM = 'custom';

    public function getColor(): string
    {
        return 'outline';
    }

    public function getText(): string
    {
        return match ($this) {
            self::NONE => 'disabled',
            self::LETSENCRYPT => 'letsencrypt',
            self::CUSTOM => 'custom',
        };
    }
}
