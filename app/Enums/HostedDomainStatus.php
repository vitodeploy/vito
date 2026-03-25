<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use App\Traits\HasEnumHelpers;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum HostedDomainStatus: string implements HasTableDisplay, VitoEnum
{
    use HasEnumHelpers;

    case CREATING = 'creating';
    case UPDATING = 'updating';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATING => 'warning',
            self::UPDATING => 'warning',
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

    public function isProcessing(): bool
    {
        return in_array($this, [self::CREATING, self::UPDATING, self::DELETING]);
    }
}
