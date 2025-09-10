<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum PHPIniType: string implements VitoEnum
{
    case CLI = 'cli';
    case FPM = 'fpm';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
