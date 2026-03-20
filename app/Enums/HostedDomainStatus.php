<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use App\Traits\HasEnumHelpers;

enum HostedDomainStatus: string implements VitoEnum
{
    use HasEnumHelpers;

    case CREATING = 'creating';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATING => 'warning',
            self::PENDING => 'default',
            self::ACTIVE => 'success',
            self::INACTIVE => 'gray',
            self::DELETING => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
