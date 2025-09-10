<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use App\Traits\HasEnumHelpers;

enum SslType: string implements VitoEnum
{
    use HasEnumHelpers;

    case LETSENCRYPT = 'letsencrypt';
    case CUSTOM = 'custom';

    public function getColor(): string
    {
        return match ($this) {
            self::LETSENCRYPT,
            self::CUSTOM => 'default',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
