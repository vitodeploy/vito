<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum VitoBackupStatus: string implements VitoEnum
{
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case DELETING = 'deleting';
    case PENDING = 'pending';

    public function getColor(): string
    {
        return match ($this) {
            self::RUNNING => 'warning',
            self::SUCCESS => 'success',
            self::FAILED,
            self::PENDING => 'danger',
            self::DELETING => 'warning',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
