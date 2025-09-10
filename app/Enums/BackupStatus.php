<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum BackupStatus: string implements VitoEnum
{
    case RUNNING = 'running';
    case FAILED = 'failed';
    case DELETING = 'deleting';
    case STOPPED = 'stopped';

    public function getColor(): string
    {
        return match ($this) {
            self::RUNNING => 'success',
            self::FAILED,
            self::STOPPED => 'danger',
            self::DELETING => 'warning',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
