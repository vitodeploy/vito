<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum NetworkStatus: string implements HasTableDisplay, VitoEnum
{
    case CREATING = 'creating';
    case SYNCING = 'syncing';
    case ACTIVE = 'active';
    case FAILED = 'failed';
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATING => 'info',
            self::SYNCING => 'warning',
            self::ACTIVE => 'success',
            self::DELETING,
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
