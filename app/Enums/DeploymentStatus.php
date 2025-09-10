<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum DeploymentStatus: string implements VitoEnum
{
    case DEPLOYING = 'deploying';
    case FINISHED = 'finished';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::DEPLOYING => 'warning',
            self::FINISHED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
