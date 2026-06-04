<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum SecurityControlStatus: string implements VitoEnum
{
    case DISABLED = 'disabled';
    case UPDATING = 'updating';
    case READY = 'ready';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::DISABLED => 'gray',
            self::UPDATING => 'warning',
            self::READY => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this === self::READY ? 'active' : $this->value;
    }
}
