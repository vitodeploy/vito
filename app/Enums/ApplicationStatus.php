<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum ApplicationStatus: string implements HasTableDisplay, VitoEnum
{
    case READY = 'ready';
    case DELETING = 'deleting';
    case DELETION_FAILED = 'deletion_failed';

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::DELETING,
            self::DELETION_FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
