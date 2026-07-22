<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum NetworkPeerStatus: string implements HasTableDisplay, VitoEnum
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'info',
            self::ACTIVE => 'success',
            self::DISABLED => 'gray',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
