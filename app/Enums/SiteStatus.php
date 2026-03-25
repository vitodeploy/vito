<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum SiteStatus: string implements HasTableDisplay, VitoEnum
{
    case READY = 'ready';
    case INSTALLING = 'installing';
    case INSTALLATION_FAILED = 'installation_failed';
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::INSTALLING => 'warning',
            self::INSTALLATION_FAILED,
            self::DELETING => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
