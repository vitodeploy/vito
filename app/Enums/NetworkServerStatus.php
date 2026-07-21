<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum NetworkServerStatus: string implements HasTableDisplay, VitoEnum
{
    case PENDING = 'pending';
    case UPDATING = 'updating';
    case ACTIVE = 'active';
    case FAILED = 'failed';
    case LEAVING = 'leaving';

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'info',
            self::UPDATING,
            self::LEAVING => 'warning',
            self::ACTIVE => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
