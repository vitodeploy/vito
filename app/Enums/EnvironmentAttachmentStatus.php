<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum EnvironmentAttachmentStatus: string implements HasTableDisplay, VitoEnum
{
    case PENDING = 'pending';
    case READY = 'ready';
    case FAILED = 'failed';
    case DELETING = 'deleting';
    case DELETION_FAILED = 'deletion_failed';

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING, self::DELETING => 'warning',
            self::READY => 'success',
            self::FAILED, self::DELETION_FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
