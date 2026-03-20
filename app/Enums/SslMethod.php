<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use App\Traits\HasEnumHelpers;

enum SslMethod: string implements VitoEnum
{
    use HasEnumHelpers;

    case NONE = 'none';
    case LETSENCRYPT = 'letsencrypt';
    case CUSTOM = 'custom';

    public function getColor(): string
    {
        return match ($this) {
            self::NONE => 'gray',
            self::LETSENCRYPT => 'info',
            self::CUSTOM => 'success',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
