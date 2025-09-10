<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ServerStatus: string implements VitoEnum
{
    case READY = 'ready';
    case INSTALLING = 'installing';
    case INSTALLATION_FAILED = 'installation_failed';
    case DISCONNECTED = 'disconnected';
    case UPDATING = 'updating';

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::INSTALLING,
            self::UPDATING => 'warning',
            self::DISCONNECTED => 'gray',
            self::INSTALLATION_FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
