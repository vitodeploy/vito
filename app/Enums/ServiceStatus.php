<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ServiceStatus: string implements VitoEnum
{
    case READY = 'ready';
    case INSTALLING = 'installing';
    case INSTALLATION_FAILED = 'installation_failed';
    case UNINSTALLING = 'uninstalling';
    case FAILED = 'failed';
    case STARTING = 'starting';
    case STOPPING = 'stopping';
    case RESTARTING = 'restarting';
    case RELOADING = 'reloading';
    case STOPPED = 'stopped';
    case ENABLING = 'enabling';
    case DISABLING = 'disabling';
    case DISABLED = 'disabled';

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::INSTALLING,
            self::STARTING,
            self::UNINSTALLING,
            self::STOPPING,
            self::RESTARTING,
            self::RELOADING,
            self::ENABLING,
            self::DISABLING => 'warning',
            self::INSTALLATION_FAILED,
            self::FAILED,
            self::STOPPED => 'danger',
            self::DISABLED => 'gray',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
