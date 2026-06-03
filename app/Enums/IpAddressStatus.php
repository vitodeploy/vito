<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum IpAddressStatus: string implements HasTableDisplay, VitoEnum
{
    case CONFIGURING = 'configuring';
    case CONFIGURED = 'configured';
    case DELETING = 'deleting';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::CONFIGURING => 'info',
            self::CONFIGURED => 'success',
            self::DELETING => 'warning',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return match ($this) {
            self::CONFIGURED => 'active',
            default => $this->value,
        };
    }
}
