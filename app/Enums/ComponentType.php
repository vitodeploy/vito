<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ComponentType: string implements VitoEnum
{
    case APP = 'app';
    case LOAD_BALANCER = 'load_balancer';

    public function getColor(): string
    {
        return match ($this) {
            self::APP => 'info',
            self::LOAD_BALANCER => 'warning',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
