<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum WebMode: string implements VitoEnum
{
    case FPM = 'fpm';
    case PROXY = 'proxy';
    case STATIC = 'static';

    public function getColor(): string
    {
        return match ($this) {
            self::FPM => 'info',
            self::PROXY => 'warning',
            self::STATIC => 'gray',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
