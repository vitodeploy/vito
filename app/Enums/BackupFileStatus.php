<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum BackupFileStatus: string implements VitoEnum
{
    case CREATED = 'created';
    case CREATING = 'creating';
    case FAILED = 'failed';
    case DELETING = 'deleting';
    case RESTORING = 'restoring';
    case RESTORED = 'restored';
    case RESTORE_FAILED = 'restore_failed';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATED => 'success',
            self::CREATING,
            self::DELETING,
            self::RESTORING => 'warning',
            self::FAILED,
            self::RESTORE_FAILED => 'danger',
            self::RESTORED => 'primary',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
