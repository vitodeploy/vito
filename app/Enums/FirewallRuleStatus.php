<?php

namespace App\Enums;

enum FirewallRuleStatus: string
{
    case CREATING = 'creating';
    case UPDATING = 'updating';
    case READY = 'ready';
    case DELETING = 'deleting';
    case FAILED = 'failed';

    public function getText(): string
    {
        return match ($this) {
            self::CREATING => 'Creating',
            self::UPDATING => 'Updating',
            self::READY => 'Active',
            self::DELETING => 'Deleting',
            self::FAILED => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CREATING, self::UPDATING, self::DELETING => 'primary',
            self::READY => 'success',
            self::FAILED => 'danger',
        };
    }
}
