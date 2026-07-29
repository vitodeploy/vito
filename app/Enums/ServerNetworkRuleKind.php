<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ServerNetworkRuleKind: string implements VitoEnum
{
    case HANDSHAKE = 'handshake';
    case RULE = 'rule';

    public function getColor(): string
    {
        return match ($this) {
            self::HANDSHAKE => 'info',
            self::RULE => 'success',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
