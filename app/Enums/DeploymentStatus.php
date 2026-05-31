<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum DeploymentStatus: string implements HasTableDisplay, VitoEnum
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
