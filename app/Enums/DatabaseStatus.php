<?php

namespace App\Enums;

enum DatabaseStatus: string
{
    case READY = 'ready';
    case CREATING = 'creating';
    case FAILED = 'failed';
    case DELETING = 'deleting';

    public function getText(): string
    {
        return match ($this) {
            self::READY => 'Ready',
            self::CREATING => 'Creating',
            self::FAILED => 'Failed',
            self::DELETING => 'Deleting',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CREATING, self::DELETING => 'primary',
            self::READY => 'success',
            self::FAILED => 'error',
        };
    }
}
