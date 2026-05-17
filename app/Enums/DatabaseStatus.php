<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum DatabaseStatus: string implements HasTableDisplay, VitoEnum
{
    case READY = 'ready';
    case CREATING = 'creating';
    case FAILED = 'failed';
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::CREATING,
            self::DELETING => 'warning',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
