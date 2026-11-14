<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ComponentStatus: string implements VitoEnum
{
    case PENDING = 'pending';
    case INSTALLING = 'installing';
    case READY = 'ready';
    case INSTALLATION_FAILED = 'installation_failed';
    case DELETING = 'deleting';
    case DELETION_FAILED = 'deletion_failed';

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::INSTALLING => 'warning',
            self::READY => 'success',
            self::INSTALLATION_FAILED,
            self::DELETING,
            self::DELETION_FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
